<?php

namespace App\Services;

use App\Models\CommercialSubscription;
use Illuminate\Database\Eloquent\Builder;

class CommercialMembershipReminders
{
    public function query(): Builder
    {
        return CommercialSubscription::query()->select('commercial_subscriptions.*')
            ->selectRaw('COALESCE(paid_until, trial_ends_at) AS reminder_due_at')
            ->where('billing_mode', 'manual')->whereNull('provider_id')->where('cancel_at_period_end', false)
            ->whereHas('user', fn ($owner) => $owner->where('commercial_access_enabled', true))
            ->where(fn ($kind) => $kind->where('kind', 'coaching')->orWhere(fn ($software) => $software->where('kind', 'software')->whereHas('gym', fn ($gym) => $gym->where('billing_managed', true))))
            ->whereRaw('COALESCE(paid_until, trial_ends_at) >= ?', [now()->startOfDay()->subDays(30)->format('Y-m-d H:i:s')])
            ->whereRaw('COALESCE(paid_until, trial_ends_at) <= ?', [now()->endOfDay()->addDays(7)->format('Y-m-d H:i:s')])
            ->with(['user:id,name,email', 'gym:id,name'])->orderByRaw('COALESCE(paid_until, trial_ends_at)')->orderBy('id');
    }
}
