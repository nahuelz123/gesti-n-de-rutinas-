<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Assignment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'gym_id',
        'routine_id',
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

    public function routine(): BelongsTo
    {
        // Importante para historial: si una rutina fue soft-deleted, igual queremos verla
        return $this->belongsTo(Routine::class)->withTrashed();
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
        return $this->hasMany(ExerciseLog::class);
    }

    protected static function booted(): void
    {
        static::saving(function (Assignment $assignment) {
            $actor = Auth::user();
            if (! $actor) return;

            // Fuerza gym_id (salvo super_admin)
            if ($actor->role !== 'super_admin') {
                $assignment->gym_id = $actor->gym_id;
            }

            // Si no viene assigned_by_id, setea el actor
            if (! $assignment->assigned_by_id) {
                $assignment->assigned_by_id = $actor->id;
            }

            // Validaciones anti cross-gym (solo si NO es super_admin)
            if ($actor->role !== 'super_admin') {
                $client = User::query()->find($assignment->client_id);

                if (! $client || $client->gym_id !== $actor->gym_id || $client->role !== 'client') {
                    throw new \RuntimeException('Cliente inválido para este gimnasio.');
                }

                $routine = Routine::query()->withTrashed()->find($assignment->routine_id);

                if (! $routine || $routine->gym_id !== $actor->gym_id) {
                    throw new \RuntimeException('Rutina inválida para este gimnasio.');
                }

                $assigner = User::query()->find($assignment->assigned_by_id);

                if (! $assigner || $assigner->gym_id !== $actor->gym_id || ! in_array($assigner->role, ['admin', 'coach'])) {
                    throw new \RuntimeException('Assigned_by inválido para este gimnasio.');
                }
            }
        });
    }
}
