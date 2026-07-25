<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BodyMeasurement extends Model
{
    protected $fillable = [
        'client_id', 'measured_at', 'weight', 'waist', 'chest', 'hip',
        'arm', 'thigh', 'neck', 'body_fat_percentage', 'notes',
    ];

    protected $casts = [
        'measured_at' => 'date',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * Método US Navy. Devuelve null si faltan datos (altura/sexo del perfil,
     * o cintura/cuello de esta medición, o cadera si es mujer).
     */
    public static function calculateBodyFat(User $client, ?float $waist, ?float $neck, ?float $hip): ?float
    {
        $height = $client->height_cm;
        $sex = $client->sex;

        if (! $height || ! $sex || ! $waist || ! $neck) {
            return null;
        }

        if ($sex === 'f' && ! $hip) {
            return null;
        }

        if ($sex === 'm') {
            $waistNeck = $waist - $neck;
            if ($waistNeck <= 0) return null;

            $bf = 495 / (1.0324 - 0.19077 * log10($waistNeck) + 0.15456 * log10($height)) - 450;
        } else {
            $sum = $waist + $hip - $neck;
            if ($sum <= 0) return null;

            $bf = 495 / (1.29579 - 0.35004 * log10($sum) + 0.22100 * log10($height)) - 450;
        }

        // Clamp a un rango humano razonable, por si los datos cargados son raros
        $bf = max(3, min(60, $bf));

        return round($bf, 1);
    }
}
