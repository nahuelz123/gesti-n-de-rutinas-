<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class DietAssignment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'gym_id',
        'diet_plan_id',
        'client_id',
        'assigned_by_id',
        'assigned_at',
        'start_date',
        'end_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'start_date'  => 'date',
        'end_date'    => 'date',
    ];

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function dietPlan(): BelongsTo
    {
        // Importante para historial: si un plan fue soft-deleted, igual queremos verlo
        return $this->belongsTo(DietPlan::class)->withTrashed();
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(MealLog::class);
    }

    protected static function booted(): void
    {
        static::saving(function (DietAssignment $assignment) {
            $actor = Auth::user();
            if (! $actor) return;

            if ($actor->role !== 'super_admin') {
                $assignment->gym_id = $actor->gym_id;
            }

            if (! $assignment->assigned_by_id) {
                $assignment->assigned_by_id = $actor->id;
            }

            if ($actor->role !== 'super_admin') {
                $client = User::query()->find($assignment->client_id);

                if (! $client || $client->gym_id !== $actor->gym_id || $client->role !== 'client') {
                    throw new \RuntimeException('Cliente inválido para este gimnasio.');
                }

                $plan = DietPlan::query()->withTrashed()->find($assignment->diet_plan_id);

                if (! $plan || $plan->gym_id !== $actor->gym_id) {
                    throw new \RuntimeException('Plan de dieta inválido para este gimnasio.');
                }
            }
        });
    }
}
