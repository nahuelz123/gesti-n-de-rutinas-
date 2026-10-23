<?php

namespace App\Http\Controllers;

use App\Models\CommercialPayment;
use App\Models\CommercialSubscription;
use App\Services\ManualMembershipPayments;
use Illuminate\Http\Request;

class CommercialManualPaymentsController extends Controller
{
    public function store(Request $request, CommercialSubscription $subscription, ManualMembershipPayments $payments)
    {
        $payments->record($subscription, $request->user(), $request->all());

        return redirect('/gestion-comercial/membresias/'.$subscription->id)->with('status', 'Cobro confirmado registrado. El período figura en el historial.');
    }

    public function refund(Request $request, CommercialPayment $payment, ManualMembershipPayments $payments)
    {
        $payments->refund($payment, $request->user(), $request->all());

        return redirect('/gestion-comercial/membresias/'.$payment->commercial_subscription_id)->with('status', 'Reintegro registrado. VisionFit no ejecutó una transferencia de dinero.');
    }

    public function fiscal(Request $request, CommercialPayment $payment, ManualMembershipPayments $payments)
    {
        $payments->annotateFiscalReference($payment, $request->user(), $request->all());

        return redirect('/gestion-comercial/membresias/'.$payment->commercial_subscription_id)->with('status', 'Referencia fiscal anotada. No se emitió una factura desde VisionFit.');
    }
}
