<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Gym extends Model
{
    protected $fillable = [
        'name',
        'logo',
        'plan',
        'active',
        'invite_code',
    ];

    protected static function booted(): void
    {
        static::creating(function (Gym $gym) {
            if (! $gym->invite_code) {
                $gym->invite_code = static::generateUniqueInviteCode();
            }
        });
    }

    public static function generateUniqueInviteCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (static::where('invite_code', $code)->exists());

        return $code;
    }

    /**
     * Link que se codifica en el QR del gimnasio para que el alumno se una directo.
     */
    public function joinUrl(): string
    {
        return route('gym-join.show', $this->invite_code);
    }

    public function qrImageUrl(): string
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&data='.urlencode($this->joinUrl());
    }

    /**
     * URL completa del logo, lista para usar en un <img src="">.
     * Soporta tanto archivos subidos por Filament (ruta relativa en el disco
     * 'public') como URLs absolutas cargadas a mano en el pasado.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo) {
            return null;
        }

        if (str_starts_with($this->logo, 'http://') || str_starts_with($this->logo, 'https://')) {
            return $this->logo;
        }

        return Storage::disk('public')->url($this->logo);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function exercises(): HasMany
    {
        return $this->hasMany(Exercise::class);
    }

    public function routines(): HasMany
    {
        return $this->hasMany(Routine::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function dietPlans(): HasMany
    {
        return $this->hasMany(DietPlan::class);
    }

    public function dietAssignments(): HasMany
    {
        return $this->hasMany(DietAssignment::class);
    }
}
