<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class DietPlan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'gym_id',
        'coach_id',
        'title',
        'description',
        'goal',
        'target_calories',
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
        return $this->hasMany(DietPlanDay::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(DietAssignment::class);
    }

    protected static function booted(): void
    {
        static::creating(function (DietPlan $plan) {
            $user = Auth::user();
            if (! $user) return;

            if ($user->role !== 'super_admin') {
                $plan->gym_id = $user->gym_id;
            }

            if (! $plan->coach_id) {
                $plan->coach_id = $user->id;
            }
        });
    }
}
