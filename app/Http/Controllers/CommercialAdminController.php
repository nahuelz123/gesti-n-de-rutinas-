<?php

namespace App\Http\Controllers;

use App\Models\CommercialInquiry;
use App\Models\CommercialSubscription;
use App\Models\User;
use App\Support\CommercialCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CommercialAdminController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->role === 'super_admin', 403);
        $status = $request->query('status');
        $inquiries = CommercialInquiry::query()->when(in_array($status, ['new', 'contacted', 'closed']), fn ($query) => $query->where('status', $status))->latest()->paginate(20)->withQueryString();

        $coachingClients = User::where('gym_id', config('commerce.coaching_gym_id'))->where('role', 'client')->whereNotIn('id', CommercialSubscription::select('user_id'))->orderBy('name')->limit(200)->get();
        $subscriptions = CommercialSubscription::with('user', 'gym')->latest()->limit(50)->get();

        return view('marketing.admin', compact('inquiries', 'coachingClients', 'subscriptions') + ['meta' => ['title' => 'Gestión comercial | VisionFit', 'description' => 'Solicitudes y consultas comerciales.', 'path' => '/gestion-comercial']]);
    }

    public function update(Request $request, CommercialInquiry $inquiry)
    {
        abort_unless($request->user()->role === 'super_admin', 403);
        $inquiry->update($request->validate(['status' => ['required', Rule::in(['new', 'contacted', 'closed'])]]));

        return redirect('/gestion-comercial')->with('status', 'Solicitud actualizada.');
    }

    public function coaching(Request $request)
    {
        abort_unless($request->user()->role === 'super_admin', 403);
        $data = $request->validate(['user_id' => ['required', 'integer', 'exists:users,id', 'unique:commercial_subscriptions,user_id'], 'plan_code' => ['required', Rule::in(['asesoria', 'asesoria-premium'])]]);
        $user = User::findOrFail($data['user_id']);
        abort_unless(config('commerce.coaching_gym_id') && $user->gym_id === (int) config('commerce.coaching_gym_id') && $user->role === 'client', 422);
        $plan = CommercialCatalog::plan($data['plan_code']);
        CommercialSubscription::create(['public_id' => (string) Str::uuid(), 'user_id' => $user->id, 'kind' => 'coaching', 'plan_code' => $data['plan_code'], 'amount_cents' => $plan['price'] * 100, 'currency' => 'ARS', 'status' => 'awaiting_payment', 'terms_version' => config('legal.versions.terms')]);

        return redirect('/gestion-comercial')->with('status', 'Asesoría preparada. El cliente podrá contratarla desde Mi suscripción; todavía no se cobró.');
    }
}
