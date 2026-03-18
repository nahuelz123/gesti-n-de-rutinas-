<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Routine extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'gym_id',
        'coach_id',
        'title',
        'description',
    ];

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function days(): HasMany
    {
        return $this->hasMany(RoutineDay::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }
  

protected static function booted(): void
{
    static::creating(function (Routine $routine) {
        $user = Auth::user();
        if (! $user) return;

        if ($user->role !== 'super_admin') {
            $routine->gym_id = $user->gym_id;
        }

        // si crea un coach/admin, guardamos quién la creó como coach_id (si aplica)
        if (! $routine->coach_id) {
            $routine->coach_id = $user->id;
        }
    });
}

}
