<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

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
        return $this->belongsTo(Exercise::class)->withTrashed();
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ExerciseLog::class, 'routine_day_exercise_id');
    }

    protected static function booted(): void
    {
        static::deleting(function (RoutineDayExercise $exercise): void {
            $exercise->ensureHistoryIsPreserved();
        });

        static::updating(function (RoutineDayExercise $exercise): void {
            if ($exercise->isDirty(['exercise_id', 'routine_day_id'])) {
                $exercise->ensureHistoryIsPreserved();
            }
        });
    }

    private function ensureHistoryIsPreserved(): void
    {
        if ($this->logs()->exists()) {
            throw ValidationException::withMessages([
                'data.days' => 'Este ejercicio tiene series registradas. Creá otra rutina para reemplazarlo o quitarlo y conservar el historial del alumno.',
            ]);
        }
    }
}
