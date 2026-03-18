<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoutineDayExercise extends Model
{
    protected $fillable = [
        'routine_day_id',
        'exercise_id',
        'sets',
        'reps',
        'rest',
        'notes',
        'order',
    ];

    public function routineDay(): BelongsTo
    {
        return $this->belongsTo(RoutineDay::class, 'routine_day_id');
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ExerciseLog::class, 'routine_day_exercise_id');
    }
}
