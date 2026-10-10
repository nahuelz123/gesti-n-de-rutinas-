<?php

namespace App\Support;

use App\Models\Exercise;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ExerciseNames
{
    private const GROUPS = [
        ['label' => 'Estocadas', 'terms' => [
            'estocada', 'estocadas', 'zancada', 'zancadas', 'lunge', 'lunges',
            'Zancadas caminando', 'Estocadas caminando', 'Walking lunge',
            'Barbell lunge', 'Dumbbell lunge', 'Reverse lunge',
        ]],
        ['label' => 'Sentadilla', 'terms' => [
            'sentadilla', 'sentadillas', 'squat', 'squats',
            'Sentadilla con barra', 'Barbell squat', 'Bodyweight squat',
        ]],
        ['label' => 'Sillón de cuádriceps', 'terms' => [
            'sillón', 'sillon', 'sillón de cuádriceps', 'sillon de cuadriceps',
            'extensión de cuádriceps', 'extension de cuadriceps',
            'extensiones de cuádriceps', 'extensiones de cuadriceps',
            'extensión de piernas', 'extension de piernas',
            'Lever leg extension', 'Leg extension machine',
        ]],
        ['label' => 'Camilla de isquios', 'terms' => [
            'camilla', 'camilla de isquios', 'camilla de isquiotibiales', 'camilla de femorales',
            'curl femoral acostado', 'curl femoral tumbado', 'curl de piernas acostado',
            'Lever lying leg curl', 'Lying leg curl', 'Prone leg curl',
        ]],
        ['label' => 'Sentadilla búlgara', 'terms' => [
            'búlgara', 'bulgara', 'búlgaras', 'bulgaras', 'sentadilla búlgara', 'sentadilla bulgara',
            'Bulgarian split squat', 'Dumbbell Bulgarian split squat', 'Barbell Bulgarian split squat',
        ]],
    ];

    public static function normalize(string $name): string
    {
        return Str::lower(Str::ascii(preg_replace('/\\s+/u', ' ', trim($name))));
    }

    /** @return array<string> */
    public static function searchTerms(string $search): array
    {
        $search = mb_substr(trim($search), 0, 150);
        $normalized = self::normalize($search);
        if ($normalized === '') {
            return [];
        }
        $terms = [$search, $normalized];
        foreach (self::GROUPS as $group) {
            foreach ($group['terms'] as $term) {
                if (str_starts_with(self::normalize($term), $normalized)) {
                    $terms = array_merge($terms, $group['terms']);
                    break;
                }
            }
        }

        return array_values(array_unique(array_map(Str::lower(...), $terms)));
    }

    public static function displayName(string $title): string
    {
        $normalized = self::normalize($title);
        foreach (self::GROUPS as $group) {
            if (in_array($normalized, array_map(self::normalize(...), $group['terms']), true)) {
                return str_contains($normalized, self::normalize($group['label']))
                    ? $title : $group['label'].' · '.$title;
            }
        }

        return $title;
    }

    /** Resolver únicamente equivalencias exactas y sin variantes ambiguas. */
    public static function uniqueMatch(Collection $catalog, string $name): ?Exercise
    {
        $normalized = self::normalize($name);
        $exact = $catalog->filter(fn (Exercise $exercise): bool => self::normalize($exercise->title) === $normalized);
        if ($exact->isNotEmpty()) {
            return $exact->count() === 1 ? $exact->first() : null;
        }
        foreach (self::GROUPS as $group) {
            $terms = array_map(self::normalize(...), $group['terms']);
            if (! in_array($normalized, $terms, true)) {
                continue;
            }
            $matches = $catalog->filter(fn (Exercise $exercise): bool => in_array(self::normalize($exercise->title), $terms, true));

            return $matches->count() === 1 ? $matches->first() : null;
        }

        return null;
    }
}
