<?php

namespace App\Services;

use App\Support\ActivityCatalog;
use InvalidArgumentException;

class ActivityEnergy
{
    public function estimate(string $key, int $minutes, float $weight): array
    {
        $activity = ActivityCatalog::all()[$key] ?? null;
        if (! $activity || $minutes < 1 || $minutes > 600 || ! is_finite($weight) || $weight < 20 || $weight > 400) {
            throw new InvalidArgumentException('Actividad, duración o peso fuera del rango admitido.');
        }

        // One standard MET is 1 kcal/kg/hour. Subtract resting expenditure for net activity kcal.
        return ['activity_key' => $key, 'activity_label' => $activity['label'], 'category' => $activity['category'],
            'met' => $activity['met'], 'source_code' => $activity['source_code'],
            'active_kcal' => round(($activity['met'] - 1) * $weight * $minutes / 60, 2),
            'calculation_version' => 'compendium2024-net-v1'];
    }
}
