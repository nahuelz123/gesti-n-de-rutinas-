<?php

namespace App\Services;

use App\Models\CommercialSubscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class MercadoPagoSubscriptions
{
    public function ready(): bool
    {
        return filled(config('commerce.mercadopago_token')) && filled(config('commerce.mercadopago_webhook_secret'))
            && filled(config('legal.operator_name')) && filter_var(config('legal.contact_email'), FILTER_VALIDATE_EMAIL);
    }

    public function request(string $method, string $path, array $data = []): array
    {
        return Http::withToken(config('commerce.mercadopago_token'))->acceptJson()->timeout(15)
            ->send($method, 'https://api.mercadopago.com'.$path, $data ? ['json' => $data] : [])->throw()->json();
    }

    public function checkout(CommercialSubscription $subscription): string
    {
        if (! $this->ready()) {
            throw ValidationException::withMessages(['billing' => 'El cobro online todavía no está habilitado. Podés coordinar la activación desde Contacto.']);
        }
        if ($subscription->cancel_at_period_end) {
            throw ValidationException::withMessages(['billing' => 'La renovación está cancelada. Contactanos para acordar una nueva contratación.']);
        }
        if ($subscription->provider_id && $subscription->checkout_url) {
            return $subscription->checkout_url;
        }
        $result = $this->request('POST', '/preapproval', [
            'reason' => 'VisionFit · '.config('commerce.plans.'.$subscription->plan_code.'.name', 'Suscripción'),
            'external_reference' => $subscription->public_id,
            'payer_email' => $subscription->user->email,
            'auto_recurring' => ['frequency' => 1, 'frequency_type' => 'months', 'transaction_amount' => $subscription->amount_cents / 100, 'currency_id' => $subscription->currency],
            'back_url' => url('/mi-suscripcion'), 'status' => 'pending',
        ]);
        $url = $result['init_point'] ?? '';
        if (empty($result['id']) || parse_url($url, PHP_URL_SCHEME) !== 'https' || ! in_array(parse_url($url, PHP_URL_HOST), ['www.mercadopago.com.ar', 'mercadopago.com.ar'])) {
            throw ValidationException::withMessages(['billing' => 'No pudimos preparar un enlace de pago válido. Volvé a intentar.']);
        }
        $subscription->update(['provider_id' => $result['id'], 'provider_status' => $result['status'] ?? 'pending', 'checkout_url' => $url, 'renewal_consented_at' => now()]);

        return $url;
    }

    public function cancel(CommercialSubscription $subscription): void
    {
        if ($subscription->cancel_at_period_end) {
            return;
        }
        if ($subscription->provider_id) {
            if (! $this->ready()) {
                throw ValidationException::withMessages(['billing' => 'No pudimos conectar con el servicio de cobros. Registrá tu solicitud desde Baja de servicio.']);
            }
            $result = $this->request('PUT', '/preapproval/'.rawurlencode($subscription->provider_id), ['status' => 'cancelled']);
            if (($result['status'] ?? null) !== 'cancelled') {
                throw ValidationException::withMessages(['billing' => 'La cancelación todavía no fue confirmada. Volvé a intentar o registrá una solicitud de baja.']);
            }
        }
        $subscription->update(['cancel_at_period_end' => true, 'status' => 'cancelled', 'provider_status' => 'cancelled']);
    }
}
