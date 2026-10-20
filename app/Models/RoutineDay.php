<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

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

    protected static function booted(): void
    {
        static::deleting(function (RoutineDay $day): void {
            $day->ensureHistoryIsPreserved();
        });
        static::updating(function (RoutineDay $day): void {
            if ($day->isDirty('routine_id')) {
                $day->ensureHistoryIsPreserved();
            }
        });
    }

    private function ensureHistoryIsPreserved(): void
    {
        if ($this->exercises()->whereHas('logs')->exists()) {
            throw ValidationException::withMessages([
                'data.days' => 'Este día tiene series registradas. Creá otra rutina para quitarlo o moverlo y conservar el historial del alumno.',
            ]);
        }
    }
}
