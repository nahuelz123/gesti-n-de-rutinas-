<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
class Exercise extends Model
{
    protected $fillable = [
        'gym_id',
        'title',
        'muscle_group',
        'description',
        'tips',
        'video_url',
    ];

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function routineDayExercises(): HasMany
    {
        return $this->hasMany(RoutineDayExercise::class);
    }

    

protected static function booted(): void
{
    static::saving(function (Exercise $exercise) {
        $user = Auth::user();
        if (! $user) return;

        // Si no es super_admin, NO puede crear globales:
        if ($user->role !== 'super_admin') {
            $exercise->gym_id = $user->gym_id;
            $exercise->is_global = false;
        }
    });
}

}
