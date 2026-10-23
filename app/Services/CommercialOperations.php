<?php

namespace App\Services;

use App\Models\CommercialOperation;
use App\Models\CommercialSubscription;
use App\Models\Gym;
use App\Models\User;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CommercialOperations
{
    public function execute(CommercialSubscription $subscription, User $actor, string $id, string $action, array $payload, Closure $perform): CommercialOperation
    {
        abort_unless($actor->role === 'super_admin' || ($action === 'subscription_cancelled' && $actor->id === $subscription->user_id), 403);
        Validator::make(['operation_id' => $id], ['operation_id' => ['required', 'uuid']])->validate();
        ksort($payload);
        $hash = hash('sha256', json_encode([$subscription->id, $action, $payload], JSON_THROW_ON_ERROR));
        try {
            return DB::transaction(function () use ($subscription, $actor, $id, $action, $payload, $perform, $hash) {
                // Share the member writer's lock before any snapshot read. This
                // also orders quota changes consistently on MySQL REPEATABLE READ.
                if ($subscription->gym_id) {
                    Gym::lockForUpdate()->findOrFail($subscription->gym_id);
                }
                $locked = CommercialSubscription::lockForUpdate()->findOrFail($subscription->id);
                $existing = CommercialOperation::where('public_id', $id)->first();
                if ($existing) {
                    if (! hash_equals($existing->request_hash, $hash)) {
                        throw ValidationException::withMessages(['operation_id' => 'Esta operación ya se usó con otros datos. Actualizá la pantalla antes de continuar.']);
                    }

                    return $existing;
                }
                if (! $locked->isManual()) {
                    throw ValidationException::withMessages(['billing' => 'Esta suscripción está vinculada al proveedor. No se registran movimientos manuales sobre ella.']);
                }
                $result = $perform($locked);

                return CommercialOperation::create(['public_id' => $id, 'commercial_subscription_id' => $locked->id, 'actor_id' => $actor->id, 'actor_name' => $actor->name, 'action' => $action, 'request_hash' => $hash, 'payload' => $payload, 'result' => $result]);
            }, 3);
        } catch (QueryException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '23505'])) {
                throw ValidationException::withMessages(['operation_id' => 'Otra operación se registró al mismo tiempo. Actualizá la pantalla y revisá el historial antes de volver a confirmar.']);
            }
            throw $exception;
        }
    }
}
