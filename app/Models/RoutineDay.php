<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoutineDay extends Model
{
    protected $fillable = [
        'routine_id',
        'day_number',
        'title',
    ];

    public function routine(): BelongsTo
    {
        return $this->belongsTo(Routine::class);
    }

    public function exercises(): HasMany
    {
        return $this->hasMany(RoutineDayExercise::class, 'routine_day_id');
    }
}
