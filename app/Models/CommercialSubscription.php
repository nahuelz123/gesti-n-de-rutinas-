<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommercialSubscription extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['trial_ends_at' => 'datetime', 'paid_until' => 'datetime', 'cancel_at_period_end' => 'boolean', 'renewal_consented_at' => 'datetime', 'last_synced_at' => 'datetime', 'amount_cents' => 'integer'];
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

    public function hasAccess(): bool
    {
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
}
