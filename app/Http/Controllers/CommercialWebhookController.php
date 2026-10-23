<?php

namespace App\Http\Controllers;

use App\Services\CommercialBilling;
use Illuminate\Http\Request;

class CommercialWebhookController extends Controller
{
    public function __invoke(Request $request, CommercialBilling $billing)
    {
        $secret = config('commerce.mercadopago_webhook_secret');
        abort_unless(filled($secret) && filled(config('commerce.mercadopago_token')), 503);
        $id = (string) ($request->query('data_id') ?? $request->query('data.id') ?? $request->input('data.id', ''));
        $requestId = $request->header('x-request-id', '');
        $parts = [];
        foreach (explode(',', $request->header('x-signature', '')) as $part) {
            $pair = explode('=', trim($part), 2);
            if (count($pair) === 2) {
                $parts[$pair[0]] = $pair[1];
            }
        }
        abort_unless(preg_match('/^[a-zA-Z0-9_-]{1,150}$/', $id) && $requestId && isset($parts['ts'], $parts['v1']), 401);
        $manifest = 'id:'.strtolower($id).';request-id:'.$requestId.';ts:'.$parts['ts'].';';
        abort_unless(hash_equals(hash_hmac('sha256', $manifest, $secret), $parts['v1']), 401);
        match ($request->input('type')) {
            'subscription_preapproval' => $billing->syncPreapproval($id),
            'subscription_authorized_payment' => $billing->syncAuthorizedPayment($id),
            'payment' => $billing->syncPayment($id),
            default => null,
        };

        return response()->json(['received' => true]);
    }
}
