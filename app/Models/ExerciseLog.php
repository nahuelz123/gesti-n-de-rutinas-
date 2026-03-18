<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExerciseLog extends Model
{
    protected $fillable = [
        'assignment_id',
        'routine_day_exercise_id',
        'set_number',
        'weight',
        'reps',
        'logged_at',
    ];

    protected $casts = [
        'logged_at' => 'datetime',
        'weight' => 'decimal:2',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function routineDayExercise(): BelongsTo
    {
        return $this->belongsTo(RoutineDayExercise::class);
    }
}
