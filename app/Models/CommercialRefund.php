<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommercialRefund extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['performed_on' => 'date', 'amount_cents' => 'integer'];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(CommercialPayment::class, 'commercial_payment_id');
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Un reintegro registrado no se edita.'));
        static::deleting(fn () => throw new \LogicException('Un reintegro registrado no se borra.'));
    }
}
