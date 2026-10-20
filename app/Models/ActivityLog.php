<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityLog extends Model
{
    use SoftDeletes;

    protected $fillable = ['client_id', 'performed_on', 'activity_key', 'activity_label', 'category',
        'duration_minutes', 'body_weight', 'met', 'source_code', 'active_kcal', 'calculation_version',
        'request_token', 'notes'];

    protected $casts = ['performed_on' => 'immutable_date', 'body_weight' => 'decimal:2',
        'met' => 'decimal:2', 'active_kcal' => 'decimal:2', 'duration_minutes' => 'integer'];
}
