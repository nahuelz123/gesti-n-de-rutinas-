<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommercialPayment extends Model
{
    protected $guarded = ['id'];

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected function casts(): array
    {
        return ['period_start' => 'datetime', 'period_end' => 'datetime', 'provider_updated_at' => 'datetime'];
    }
}
