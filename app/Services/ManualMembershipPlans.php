<?php

namespace App\Services;

use App\Models\CommercialOperation;
use App\Models\CommercialSubscription;
use App\Models\Gym;
use App\Models\User;
use App\Support\CommercialCatalog;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ManualMembershipPlans
{
    public function __construct(private CommercialOperations $operations, private CommercialPricing $pricing) {}

    public function assign(User $owner, User $actor, string $plan, string $operationId, string $quote): CommercialSubscription
    {
        abort_unless($actor->role === 'super_admin', 403);
        Validator::make(['plan_code' => $plan, 'operation_id' => $operationId, 'catalog_quote' => $quote], ['plan_code' => ['required', Rule::in(array_keys(CommercialCatalog::plans()))], 'operation_id' => ['required', 'uuid'], 'catalog_quote' => ['required', 'string', 'size:64']])->validate();
        $payload = ['owner_id' => $owner->id, 'plan_code' => $plan, 'catalog_quote' => $quote];
        $existing = CommercialOperation::where('public_id', $operationId)->first();
        if ($existing) {
            if ($existing->action !== 'subscription_assigned' || ! $existing->commercial_subscription_id) {
                throw ValidationException::withMessages(['operation_id' => 'Esta referencia pertenece a otra operación. Actualizá la pantalla.']);
            }
            $sub = CommercialSubscription::findOrFail($existing->commercial_subscription_id);
            $this->operations->execute($sub, $actor, $operationId, 'subscription_assigned', $payload, fn () => []);

            return $sub;
        }
        try {
            return DB::transaction(function () use ($owner, $actor, $plan, $operationId, $payload, $quote) {
                $gym = Gym::lockForUpdate()->findOrFail($owner->gym_id);
                $account = User::lockForUpdate()->findOrFail($owner->id);
                $selected = $this->pricing->quotedPlan($plan, $quote);
                $software = $selected['kind'] === 'software';
                $valid = $software ? $account->role === 'admin' : ($account->role === 'client' && config('commerce.coaching_gym_id') && (int) $account->gym_id === (int) config('commerce.coaching_gym_id'));
                if (! $valid || $account->gym_id !== $gym->id || ! $gym->active || ! $account->commercial_access_enabled) {
                    throw ValidationException::withMessages(['user_id' => 'Elegí un administrador habilitado para la licencia o un cliente del espacio de tus asesorías.']);
                }
                if (CommercialSubscription::where('user_id', $account->id)->when($software, fn ($query) => $query->orWhere('gym_id', $gym->id))->exists()) {
                    throw ValidationException::withMessages(['user_id' => 'La cuenta o su espacio ya tienen una membresía. Abrí su gestión para continuar.']);
                }
                if ($software) {
                    $this->assertCapacity($gym, $selected);
                }
                $sub = CommercialSubscription::create(['public_id' => (string) Str::uuid(), 'user_id' => $account->id, 'gym_id' => $software ? $gym->id : null, 'kind' => $selected['kind'], 'plan_code' => $plan, 'amount_cents' => $selected['price_cents'], 'currency' => 'ARS', 'billing_mode' => 'manual', 'status' => 'awaiting_payment', 'terms_version' => config('legal.versions.terms')]);
                $this->operations->execute($sub->fresh(), $actor, $operationId, 'subscription_assigned', $payload, fn () => ['subscription_id' => $sub->id, 'legacy_access_preserved' => $software && ! $gym->billing_managed]);

                return $sub;
            }, 3);
        } catch (QueryException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '23505'])) {
                throw ValidationException::withMessages(['user_id' => 'Esta cuenta acaba de recibir una membresía. Actualizá el listado.']);
            }
            throw $exception;
        }
    }

    public function schedule(CommercialSubscription $subscription, User $actor, array $input): void
    {
        abort_unless($actor->role === 'super_admin', 403);
        $data = $this->actionData($input, ['plan_code' => ['nullable', Rule::in(array_keys(CommercialCatalog::plans()))], 'catalog_quote' => ['required_with:plan_code', 'nullable', 'string', 'size:64']]);
        $this->operations->execute($subscription, $actor, $data['operation_id'], 'plan_scheduled', $data, function ($locked) use ($data) {
            $this->applyDue($locked);
            if ($locked->next_plan_effective_at) {
                throw ValidationException::withMessages(['plan_code' => 'El cambio ya tiene un período pagado. No se modifica una contratación abonada; revisá primero su cobro.']);
            }
            $code = $data['plan_code'] ?? null;
            $selected = $code ? $this->pricing->quotedPlan($code, $data['catalog_quote']) : null;
            if ($selected && $selected['kind'] !== $locked->kind) {
                throw ValidationException::withMessages(['plan_code' => 'El nuevo plan debe corresponder al mismo tipo de membresía.']);
            }
            if ($selected && $locked->gym) {
                $this->assertCapacity($locked->gym, $selected);
            }
            $locked->update(['next_plan_code' => $code, 'next_amount_cents' => $selected ? $selected['price_cents'] : null, 'next_plan_effective_at' => null]);

            return ['next_plan_code' => $code, 'next_amount_cents' => $locked->next_amount_cents];
        });
    }

    public function cancel(CommercialSubscription $subscription, User $actor, array $input): void
    {
        abort_unless($actor->role === 'super_admin', 403);
        $this->cancelWithData($subscription, $actor, $this->actionData($input));
    }

    public function cancelByOwner(CommercialSubscription $subscription, User $actor, string $operationId): void
    {
        abort_unless($subscription->user_id === $actor->id, 403);
        $this->cancelWithData($subscription, $actor, ['operation_id' => $operationId, 'reason' => 'Baja solicitada por el titular', 'confirmed' => '1']);
    }

    private function cancelWithData(CommercialSubscription $subscription, User $actor, array $data): void
    {
        $this->operations->execute($subscription, $actor, $data['operation_id'], 'subscription_cancelled', $data, function ($locked) {
            $locked->update(['cancel_at_period_end' => true, 'status' => 'cancelled']);

            return ['paid_until' => $locked->paid_until?->toIso8601String()];
        });
    }

    public function resume(CommercialSubscription $subscription, User $actor, array $input): void
    {
        abort_unless($actor->role === 'super_admin', 403);
        $data = $this->actionData($input);
        $this->operations->execute($subscription, $actor, $data['operation_id'], 'subscription_resumed', $data, function ($locked) {
            $locked->update(['cancel_at_period_end' => false, 'status' => $locked->paid_until?->isFuture() ? 'active' : 'awaiting_payment']);

            return ['paid_until' => $locked->paid_until?->toIso8601String()];
        });
    }

    private function actionData(array $input, array $extra = []): array
    {
        return Validator::make($input, ['operation_id' => ['required', 'uuid'], 'reason' => ['required', 'string', 'min:5', 'max:500'], 'confirmed' => ['accepted']] + $extra)->validate();
    }

    public function assertCapacity(Gym $gym, array $plan): void
    {
        $students = $gym->users()->where('commercial_access_enabled', true)->where('role', 'client')->count();
        $staff = $gym->users()->where('commercial_access_enabled', true)->whereIn('role', ['admin', 'coach'])->count();
        if ($students > (int) ($plan['students'] ?? 0) || $staff > (int) ($plan['staff'] ?? 0)) {
            throw ValidationException::withMessages(['plan_code' => 'Los accesos habilitados superan el cupo del plan. Archivá accesos o elegí un plan con más capacidad.']);
        }
    }

    public function applyDue(CommercialSubscription $subscription): void
    {
        if ($subscription->next_plan_code && $subscription->next_plan_effective_at?->lessThanOrEqualTo(now())) {
            $subscription->update(['plan_code' => $subscription->next_plan_code, 'amount_cents' => $subscription->next_amount_cents, 'next_plan_code' => null, 'next_amount_cents' => null, 'next_plan_effective_at' => null]);
        }
    }
}
