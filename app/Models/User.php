<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    // Rutinas creadas (si es coach/admin)
    public function createdRoutines(): HasMany
    {
        return $this->hasMany(Routine::class, 'coach_id');
    }

    // Asignaciones donde el user es cliente
    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'client_id');
    }

    // Asignaciones hechas por el user (si es coach/admin)
    public function givenAssignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'assigned_by');
    }

    protected static function booted(): void
{
    static::saving(function (User $model) {
        $actor = Auth::user();

        if (! $actor) return;

        // Si NO es super_admin, fuerza gym_id al del actor
        if ($actor->role !== 'super_admin') {
            $model->gym_id = $actor->gym_id;
        }

        // Si NO es super_admin, no puede crear/editar usuarios a super_admin
        if ($actor->role !== 'super_admin' && $model->role === 'super_admin') {
            throw new \RuntimeException('No autorizado: no podés asignar el rol super_admin.');
        }
    });
}
}
