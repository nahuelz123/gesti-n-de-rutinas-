<?php

namespace App\Http\Controllers;

use App\Models\CommercialSubscription;
use App\Models\User;
use App\Services\ManualMembershipPlans;
use Illuminate\Http\Request;

class CommercialManualPlansController extends Controller
{
    public function store(Request $request, ManualMembershipPlans $plans)
    {
        abort_unless($request->user()->role === 'super_admin', 403);
        $data = $request->validate(['user_id' => ['required', 'integer', 'exists:users,id'], 'plan_code' => ['required', 'string'], 'operation_id' => ['required', 'uuid'], 'confirmed' => ['accepted']]);
        $subscription = $plans->assign(User::findOrFail($data['user_id']), $request->user(), $data['plan_code'], $data['operation_id']);

        return $this->back($subscription, 'Membresía preparada. No se registró ni ejecutó un cobro.');
    }

    public function schedule(Request $request, CommercialSubscription $subscription, ManualMembershipPlans $plans)
    {
        $plans->schedule($subscription, $request->user(), $request->all());

        return $this->back($subscription, 'Cambio de plan guardado para la próxima renovación.');
    }

    public function cancel(Request $request, CommercialSubscription $subscription, ManualMembershipPlans $plans)
    {
        $plans->cancel($subscription, $request->user(), $request->all());

        return $this->back($subscription, 'Membresía cancelada. Se conservan los períodos abonados.');
    }

    public function resume(Request $request, CommercialSubscription $subscription, ManualMembershipPlans $plans)
    {
        $plans->resume($subscription, $request->user(), $request->all());

        return $this->back($subscription, 'Membresía reanudada. No se extendió el período pagado.');
    }

    private function back(CommercialSubscription $subscription, string $message)
    {
        return redirect('/gestion-comercial/membresias/'.$subscription->id)->with('status', $message);
    }
}
