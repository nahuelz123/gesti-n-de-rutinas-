<?php

namespace App\Services;

use App\Models\CommercialPayment;
use App\Models\CommercialSubscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CommercialBilling
{
    public function __construct(private MercadoPagoSubscriptions $gateway) {}

    public function syncPreapproval(string $id): void
    {
        $data = $this->gateway->request('GET', '/preapproval/'.rawurlencode($id));
        $subscription = CommercialSubscription::where('provider_id', $id)->first();
        if (! $subscription) {
            return;
        }
        abort_unless(($data['external_reference'] ?? null) === $subscription->public_id, 422);
        $changes = ['provider_status' => $data['status'] ?? 'pending', 'last_synced_at' => now()];
        if (($data['status'] ?? '') === 'cancelled') {
            $changes += ['status' => 'cancelled', 'cancel_at_period_end' => true];
        }
        $subscription->update($changes);
    }

    public function reconcile(CommercialSubscription $subscription): void
    {
        if (! $subscription->provider_id) {
            return;
        }
        $this->syncPreapproval($subscription->provider_id);
        $results = $this->gateway->request('GET', '/authorized_payments/search?preapproval_id='.rawurlencode($subscription->provider_id).'&limit=20');
        foreach (array_slice($results['results'] ?? [], 0, 20) as $invoice) {
            if (isset($invoice['id'])) {
                $this->syncAuthorizedPayment((string) $invoice['id']);
            }
        }
    }

    public function syncAuthorizedPayment(string $id): void
    {
        $invoice = $this->gateway->request('GET', '/authorized_payments/'.rawurlencode($id));
        $subscription = CommercialSubscription::where('provider_id', $invoice['preapproval_id'] ?? '')->first();
        if (! $subscription || empty($invoice['payment']['id'])) {
            return;
        }
        $payment = $this->gateway->request('GET', '/v1/payments/'.rawurlencode((string) $invoice['payment']['id']));
        $this->applyPayment($subscription, $payment, $invoice['debit_date'] ?? null);
    }

    public function syncPayment(string $id): void
    {
        $payment = $this->gateway->request('GET', '/v1/payments/'.rawurlencode($id));
        // Only payment IDs already bound through a provider invoice may update access.
        $existing = CommercialPayment::where('provider_payment_id', $id)->first();
        if (! $existing) {
            return;
        }
        $subscription = CommercialSubscription::findOrFail($existing->commercial_subscription_id);
        $this->applyPayment($subscription, $payment, $existing->period_start?->toIso8601String());
    }

    private function applyPayment(CommercialSubscription $subscription, array $payment, ?string $debitDate): void
    {
        abort_unless(isset($payment['id'], $payment['status'], $payment['transaction_amount'], $payment['currency_id'], $payment['date_last_updated']), 422);
        $amount = (int) round((float) $payment['transaction_amount'] * 100);
        abort_unless($amount === $subscription->amount_cents && $payment['currency_id'] === $subscription->currency, 422);
        DB::transaction(function () use ($subscription, $payment, $amount, $debitDate) {
            $locked = CommercialSubscription::lockForUpdate()->findOrFail($subscription->id);
            $start = $debitDate ?: ($payment['date_approved'] ?? null);
            $record = CommercialPayment::firstOrNew(['provider_payment_id' => (string) $payment['id']]);
            abort_if($record->exists && $record->commercial_subscription_id !== $locked->id, 422);
            $providerUpdatedAt = Carbon::parse($payment['date_last_updated'])->utc();
            // A delayed provider response must never overwrite a newer refund or approval.
            if ($record->provider_updated_at && $providerUpdatedAt->lessThanOrEqualTo($record->provider_updated_at)) {
                return;
            }
            $record->fill(['commercial_subscription_id' => $locked->id, 'status' => $payment['status'], 'amount_cents' => $amount, 'currency' => $payment['currency_id'], 'provider_updated_at' => $providerUpdatedAt]);
            if (! $record->period_start && $start) {
                $record->period_start = Carbon::parse($start);
                $record->period_end = $record->period_start->copy()->addMonthNoOverflow();
            }
            abort_if($payment['status'] === 'approved' && ! $record->period_end, 422);
            $record->save();
            $latest = $locked->payments()->where('status', 'approved')->max('period_end');
            $locked->update(['paid_until' => $latest, 'last_synced_at' => now(), 'status' => $locked->cancel_at_period_end ? 'cancelled' : ($latest ? 'active' : 'awaiting_payment')]);
        });
    }
}
