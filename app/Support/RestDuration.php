<?php

namespace App\Support;

class RestDuration
{
    public static function seconds($rest): ?int
    {
        if ($rest === null || $rest === '') {
            return null;
        }

        if (is_numeric($rest)) {
            return max(0, (int) round((float) $rest));
        }

        $value = strtolower(trim((string) $rest));

        // Ranges use their lower bound; combined units retain both components.
        if (preg_match('/^(\d+(?:[.,]\d+)?)\s*[-–]\s*\d+(?:[.,]\d+)?\s*(min|mins|minuto|minutos)$/u', $value, $matches)) {
            return min(3600, (int) round((float) str_replace(',', '.', $matches[1]) * 60));
        }

        if (preg_match('/^(\d+)\s*(?:min|mins|minuto|minutos)\s*(\d+)\s*(?:s|seg|segs|segundo|segundos)$/u', $value, $matches)) {
            return min(3600, ((int) $matches[1] * 60) + (int) $matches[2]);
        }

        if (preg_match('/^(\d+(?:[.,]\d+)?)\s*(min|mins|minuto|minutos)$/u', $value, $matches)) {
            return max(0, (int) round((float) str_replace(',', '.', $matches[1]) * 60));
        }

        if (preg_match('/^(\d+)\s*(s|seg|segs|segundo|segundos)$/u', $value, $matches)) {
            return max(0, (int) $matches[1]);
        }

        if (preg_match('/^(\d+)\s*:\s*(\d{1,2})$/', $value, $matches)) {
            return max(0, ((int) $matches[1] * 60) + (int) $matches[2]);
        }

        if (preg_match('/(\d+)/', $value, $matches)) {
            return max(0, (int) $matches[1]);
        }

        return null;
    }
}
