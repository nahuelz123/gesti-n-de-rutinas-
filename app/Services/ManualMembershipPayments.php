<?php

namespace App\Services;

use App\Models\CommercialPayment;
use App\Models\CommercialRefund;
use App\Models\CommercialSubscription;
use App\Models\Gym;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ManualMembershipPayments
{
    public function __construct(private CommercialOperations $operations, private ManualMembershipPlans $plans) {}

    public function record(CommercialSubscription $subscription, User $actor, array $input): CommercialPayment
    {
        abort_unless($actor->role === 'super_admin', 403);
        $data = Validator::make($input, [
            'operation_id' => ['required', 'uuid'], 'amount' => ['required', 'string'], 'months' => ['required', 'integer', 'between:1,12'],
            'currency' => ['sometimes', Rule::in(['ARS'])], 'method' => ['required', Rule::in(['transfer', 'cash', 'other'])],
            'reference' => ['nullable', 'string', 'max:120', 'required_if:method,transfer'],
            'paid_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'], 'confirmed' => ['accepted'],
            'fiscal_reference' => ['nullable', 'string', 'max:120'],
        ])->validate();
        $amount = CommercialAmounts::cents($data['amount']);
        $operation = $this->operations->execute($subscription, $actor, $data['operation_id'], 'payment_recorded', $data, function ($locked) use ($data, $amount) {
            $this->plans->applyDue($locked);
            if ($locked->cancel_at_period_end) {
                throw ValidationException::withMessages(['billing' => 'La membresía está cancelada. Reanudala antes de registrar una renovación.']);
            }
            $code = $locked->next_plan_code ?: $locked->plan_code;
            $unitAmount = $locked->next_plan_code ? $locked->next_amount_cents : $locked->amount_cents;
            if ($locked->currency !== 'ARS' || $amount !== $unitAmount * (int) $data['months']) {
                throw ValidationException::withMessages(['amount' => 'El importe debe coincidir con los meses completos del plan. No se habilita acceso por un cobro parcial.']);
            }
            if ($locked->gym) {
                $gym = Gym::lockForUpdate()->findOrFail($locked->gym_id);
                $this->plans->assertCapacity($gym, config('commerce.plans.'.$code));
            }
            $reference = trim($data['reference'] ?? '');
            $referenceKey = $data['method'] === 'transfer' ? hash('sha256', Str::lower($reference)) : null;
            if ($referenceKey && CommercialPayment::where('reference_key', $referenceKey)->exists()) {
                throw ValidationException::withMessages(['reference' => 'Esta referencia de transferencia ya está registrada. Revisá el historial.']);
            }
            $start = $locked->paid_until?->isFuture() ? $locked->paid_until->copy() : now();
            $payment = CommercialPayment::create([
                'commercial_subscription_id' => $locked->id, 'provider_payment_id' => 'manual_'.$data['operation_id'],
                'source' => 'manual', 'status' => 'approved', 'amount_cents' => $amount, 'currency' => 'ARS',
                'method' => $data['method'], 'reference' => $reference ?: null, 'reference_key' => $referenceKey,
                'paid_at' => $data['paid_on'], 'fiscal_reference' => $data['fiscal_reference'] ?? null,
                'plan_code' => $code,
                'period_start' => $start, 'period_end' => $start->copy()->addMonthsNoOverflow((int) $data['months']),
            ]);
            $locked->update(['paid_until' => $payment->period_end, 'status' => 'active', 'trial_ends_at' => null]);
            if ($locked->next_plan_code && ! $locked->next_plan_effective_at) {
                $locked->update(['next_plan_effective_at' => $start]);
                $this->plans->applyDue($locked);
            }
            if ($locked->kind === 'software') {
                $locked->gym?->forceFill(['billing_managed' => true])->save();
            }

            return ['payment_id' => $payment->id, 'paid_until' => $payment->period_end->toIso8601String()];
        });

        return CommercialPayment::findOrFail($operation->result['payment_id']);
    }

    public function refund(CommercialPayment $payment, User $actor, array $input): CommercialRefund
    {
        abort_unless($actor->role === 'super_admin', 403);
        $data = Validator::make($input, [
            'operation_id' => ['required', 'uuid'], 'amount' => ['required', 'string'], 'reason' => ['required', 'string', 'min:5', 'max:500'],
            'performed_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'], 'confirmed' => ['accepted'],
        ])->validate();
        $amount = CommercialAmounts::cents($data['amount']);
        $operation = $this->operations->execute($payment->subscription, $actor, $data['operation_id'], 'refund_recorded', $data + ['payment_id' => $payment->id], function ($locked) use ($payment, $data, $amount) {
            $record = CommercialPayment::lockForUpdate()->findOrFail($payment->id);
            if ($record->source !== 'manual' || $record->commercial_subscription_id !== $locked->id) {
                throw ValidationException::withMessages(['billing' => 'Solo se registran reintegros de cobros manuales de esta membresía.']);
            }
            if ($record->paid_at && $data['performed_on'] < $record->paid_at->toDateString()) {
                throw ValidationException::withMessages(['performed_on' => 'El reintegro no puede tener una fecha anterior al cobro.']);
            }
            if ($record->status !== 'approved' || $amount > $record->amount_cents - $record->refunded_cents) {
                throw ValidationException::withMessages(['amount' => 'El reintegro supera el saldo disponible del cobro.']);
            }
            $refund = CommercialRefund::create(['public_id' => (string) Str::uuid(), 'commercial_payment_id' => $record->id, 'amount_cents' => $amount, 'reason' => $data['reason'], 'performed_on' => $data['performed_on']]);
            $total = $record->refunded_cents + $amount;
            $record->update(['refunded_cents' => $total, 'status' => $total === $record->amount_cents ? 'refunded' : 'approved']);
            if ($locked->next_plan_effective_at && $locked->next_plan_effective_at->isFuture()) {
                $fundedFrom = $locked->payments()->where('status', 'approved')->where('plan_code', $locked->next_plan_code)
                    ->where('period_start', '>=', $locked->next_plan_effective_at->format('Y-m-d H:i:s.u'))->min('period_start');
                $locked->next_plan_effective_at = $fundedFrom;
            }
            $until = $locked->payments()->where('status', 'approved')->max('period_end');
            $locked->update(['paid_until' => $until, 'status' => $locked->cancel_at_period_end ? 'cancelled' : ($until ? 'active' : 'awaiting_payment')]);

            return ['payment_id' => $record->id, 'refund_id' => $refund->id, 'total_refunded_cents' => $total];
        });

        return CommercialRefund::findOrFail($operation->result['refund_id']);
    }

    public function annotateFiscalReference(CommercialPayment $payment, User $actor, array $input): void
    {
        abort_unless($actor->role === 'super_admin', 403);
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'fiscal_reference' => ['required', 'string', 'max:120'], 'confirmed' => ['accepted']])->validate();
        $this->operations->execute($payment->subscription, $actor, $data['operation_id'], 'fiscal_reference_added', $data + ['payment_id' => $payment->id], function ($locked) use ($payment, $data) {
            $record = CommercialPayment::lockForUpdate()->findOrFail($payment->id);
            if ($record->source !== 'manual' || $record->commercial_subscription_id !== $locked->id) {
                throw ValidationException::withMessages(['billing' => 'Elegí un cobro manual de esta membresía.']);
            }
            $previous = $record->fiscal_reference;
            $record->update(['fiscal_reference' => $data['fiscal_reference']]);

            return ['payment_id' => $record->id, 'previous_reference' => $previous, 'fiscal_reference' => $record->fiscal_reference];
        });
    }
}
