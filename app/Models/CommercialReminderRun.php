<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommercialReminderRun extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['day' => 'date', 'sent_at' => 'datetime', 'item_count' => 'integer'];
    }
}
