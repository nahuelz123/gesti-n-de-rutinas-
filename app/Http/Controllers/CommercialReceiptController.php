<?php

namespace App\Http\Controllers;

use App\Models\CommercialPayment;
use Illuminate\Http\Request;

class CommercialReceiptController extends Controller
{
    public function __invoke(Request $request, CommercialPayment $payment)
    {
        $subscription = $payment->subscription()->with('user', 'gym')->firstOrFail();
        abort_unless($request->user()->role === 'super_admin' || $subscription->user_id === $request->user()->id, 403);

        return view('marketing.commercial.receipt', ['payment' => $payment->load('refunds'), 'subscription' => $subscription, 'meta' => ['title' => 'Comprobante interno | VisionFit', 'description' => 'Registro privado de un cobro.', 'path' => '/mi-suscripcion/comprobantes/'.$payment->public_id]]);
    }
}
