<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CommercialPayment extends Model
{
    protected $guarded = ['id'];

    protected $attributes = ['source' => 'mercadopago', 'refunded_cents' => 0];

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected function casts(): array
    {
        return ['period_start' => 'datetime', 'period_end' => 'datetime', 'paid_at' => 'datetime', 'amount_cents' => 'integer', 'refunded_cents' => 'integer'];
    }

    protected function providerUpdatedAt(): Attribute
    {
        // This version column has always stored UTC, including historical rows.
        // Explicit offsets prevent a local-time cast from rejecting newer events.
        return Attribute::make(
            get: fn (?string $value) => $value === null ? null : Carbon::parse($value, 'UTC'),
            set: fn ($value) => $value === null ? null : Carbon::parse($value)->utc()->format($this->getDateFormat()),
        );
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(CommercialSubscription::class, 'commercial_subscription_id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(CommercialRefund::class);
    }

    protected static function booted(): void
    {
        static::creating(fn ($payment) => $payment->public_id ??= (string) Str::uuid());
        static::updating(function (CommercialPayment $payment) {
            if ($payment->getRawOriginal('source') === 'manual' && $payment->isDirty(['commercial_subscription_id', 'amount_cents', 'currency', 'source', 'method', 'reference', 'reference_key', 'paid_at', 'period_start', 'period_end', 'public_id', 'provider_payment_id', 'plan_code'])) {
                throw ValidationException::withMessages(['payment' => 'El cobro original no se edita. Registrá un reintegro y un nuevo movimiento si hace falta corregirlo.']);
            }
        });
        static::deleting(fn () => throw ValidationException::withMessages(['payment' => 'Los cobros registrados no se borran.']));
    }
}
