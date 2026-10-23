<?php

namespace App\Http\Controllers;

use App\Models\CommercialPayment;
use App\Models\CommercialRefund;
use App\Models\CommercialSubscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CommercialManagementController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->role === 'super_admin', 403);
        $filter = $request->validate(['search' => ['nullable', 'string', 'max:120'], 'state' => ['nullable', Rule::in(['all', 'active', 'overdue', 'trial', 'expiring', 'cancelled'])]]);
        $state = $filter['state'] ?? 'all';
        $search = $filter['search'] ?? '';
        $query = CommercialSubscription::with('user', 'gym')->withCoverage();
        if ($search !== '') {
            $query->where(fn ($q) => $q->whereHas('user', fn ($u) => $u->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'))->orWhereHas('gym', fn ($g) => $g->where('name', 'like', '%'.$search.'%')));
        }
        match ($state) {
            'active' => $query->entitled(),
            'overdue' => $query->whereNotIn('id', CommercialSubscription::entitled()->select('id'))->where('cancel_at_period_end', false),
            'trial' => $query->where('cancel_at_period_end', false)->whereNull('paid_until')->where('trial_ends_at', '>', now()),
            'cancelled' => $query->where('cancel_at_period_end', true),
            'expiring' => $query->where('cancel_at_period_end', false)->where(fn ($q) => $q->whereBetween('paid_until', [now(), now()->addDays(7)])->orWhere(fn ($trial) => $trial->whereNull('paid_until')->whereBetween('trial_ends_at', [now(), now()->addDays(7)]))),
            default => null,
        };
        $subscriptions = $query->latest('id')->paginate(20)->withQueryString();
        $accounts = User::where('commercial_access_enabled', true)->whereNotIn('id', CommercialSubscription::select('user_id'))
            ->where(fn ($q) => $q->where('role', 'admin')->orWhere(fn ($clients) => $clients->where('role', 'client')->where('gym_id', config('commerce.coaching_gym_id') ?: 0)))
            ->with('gym')->orderBy('name')->limit(300)->get();
        $month = now()->startOfMonth()->toDateString();
        $received = (int) CommercialPayment::where('source', 'manual')->whereDate('paid_at', '>=', $month)->sum('amount_cents');
        $returned = (int) CommercialRefund::whereDate('performed_on', '>=', $month)->sum('amount_cents');

        return view('marketing.commercial.index', compact('subscriptions', 'accounts', 'state', 'search', 'received', 'returned') + ['activeCount' => CommercialSubscription::entitled()->count(), 'totalCount' => CommercialSubscription::count(), 'meta' => ['title' => 'Membresías y cobros | VisionFit', 'description' => 'Gestión privada de la operación comercial.', 'path' => '/gestion-comercial/membresias']]);
    }

    public function show(Request $request, CommercialSubscription $subscription)
    {
        abort_unless($request->user()->role === 'super_admin', 403);
        $subscription->load('user', 'gym');

        return view('marketing.commercial.detail', [
            'subscription' => $subscription, 'payments' => $subscription->payments()->with('refunds')->latest('id')->paginate(12, ['*'], 'payments_page'),
            'operations' => $subscription->operations()->latest('id')->paginate(20, ['*'], 'activity_page'),
            'usage' => $subscription->gym ? ['students' => $subscription->gym->users()->where('role', 'client')->where('commercial_access_enabled', true)->count(), 'staff' => $subscription->gym->users()->whereIn('role', ['admin', 'coach'])->where('commercial_access_enabled', true)->count()] : null,
            'meta' => ['title' => 'Gestión de la membresía | VisionFit', 'description' => 'Plan, cobros y movimientos de una cuenta.', 'path' => '/gestion-comercial/membresias/'.$subscription->id],
        ]);
    }
}
