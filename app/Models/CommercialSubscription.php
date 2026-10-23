<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommercialSubscription extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['trial_ends_at' => 'datetime', 'paid_until' => 'datetime', 'cancel_at_period_end' => 'boolean', 'renewal_consented_at' => 'datetime', 'last_synced_at' => 'datetime', 'amount_cents' => 'integer', 'next_amount_cents' => 'integer', 'next_plan_effective_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CommercialPayment::class);
    }

    public function operations(): HasMany
    {
        return $this->hasMany(CommercialOperation::class);
    }

    public function currentPlanCode(): string
    {
        return $this->next_plan_code && $this->next_plan_effective_at?->lessThanOrEqualTo(now()) ? $this->next_plan_code : $this->plan_code;
    }

    public function currentAmountCents(): int
    {
        return $this->next_plan_code && $this->next_plan_effective_at?->lessThanOrEqualTo(now()) ? $this->next_amount_cents : $this->amount_cents;
    }

    public function hasAccess(): bool
    {
        $attributes = $this->getAttributes();
        $hasLedger = array_key_exists('has_ledger', $attributes) ? (bool) $this->has_ledger : $this->payments()->exists();
        if ($hasLedger) {
            $cutoff = $this->cancel_at_period_end ? now() : now()->subDays(config('commerce.grace_days'));
            $covered = array_key_exists('current_coverage', $attributes)
                ? ((bool) $this->current_coverage || (! $this->cancel_at_period_end && (bool) $this->grace_coverage))
                : $this->payments()->where('status', 'approved')
                    ->where('period_start', '<=', now()->format('Y-m-d H:i:s.u'))
                    ->where('period_end', '>', $cutoff->format('Y-m-d H:i:s.u'))->exists();

            return $covered || (! $this->cancel_at_period_end && $this->trial_ends_at?->isFuture());
        }
        if ($this->paid_until) {
            $until = $this->paid_until->copy();
            if (! $this->cancel_at_period_end) {
                $until->addDays(config('commerce.grace_days'));
            }
            if ($until->isFuture()) {
                return true;
            }
        }

        return ! $this->cancel_at_period_end && $this->trial_ends_at?->isFuture();
    }

    public function isManual(): bool
    {
        return $this->billing_mode === 'manual' && ! $this->provider_id;
    }

    public function scopeWithCoverage(Builder $query): void
    {
        $query->withExists(['payments as has_ledger',
            'payments as current_coverage' => fn ($payments) => $payments->where('status', 'approved')->where('period_start', '<=', now()->format('Y-m-d H:i:s.u'))->where('period_end', '>', now()->format('Y-m-d H:i:s.u')),
            'payments as grace_coverage' => fn ($payments) => $payments->where('status', 'approved')->where('period_start', '<=', now()->format('Y-m-d H:i:s.u'))->where('period_end', '>', now()->subDays(config('commerce.grace_days'))->format('Y-m-d H:i:s.u')),
        ]);
    }

    public function scopeEntitled(Builder $query): void
    {
        $now = now()->format('Y-m-d H:i:s.u');
        $grace = now()->subDays(config('commerce.grace_days'))->format('Y-m-d H:i:s.u');
        $query->where(function ($access) use ($now, $grace) {
            $access->where(fn ($q) => $q->where('cancel_at_period_end', false)->where('trial_ends_at', '>', now()))
                ->orWhereHas('payments', fn ($p) => $p->where('status', 'approved')->where('period_start', '<=', $now)->where('period_end', '>', $now))
                ->orWhere(fn ($q) => $q->where('cancel_at_period_end', false)->whereHas('payments', fn ($p) => $p->where('status', 'approved')->where('period_start', '<=', $now)->where('period_end', '>', $grace)))
                ->orWhere(fn ($q) => $q->whereDoesntHave('payments')->where(fn ($legacy) => $legacy->where('paid_until', '>', now())->orWhere(fn ($graced) => $graced->where('cancel_at_period_end', false)->where('paid_until', '>', now()->subDays(config('commerce.grace_days'))))));
        });
    }
}
