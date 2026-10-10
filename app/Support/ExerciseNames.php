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
        ], 'search_only' => ['Curl femoral']],
        ['label' => 'Sentadilla búlgara', 'terms' => [
            'búlgara', 'bulgara', 'búlgaras', 'bulgaras', 'sentadilla búlgara', 'sentadilla bulgara',
            'Bulgarian split squat', 'Dumbbell Bulgarian split squat', 'Barbell Bulgarian split squat',
        ]],
    ];


    // La búsqueda muestra familias; la importación conserva equipo y variante.
    private const IMPORT_EQUIVALENTS = [
        ['estocada', 'estocadas', 'zancada', 'zancadas', 'lunge', 'lunges'],
        ['estocadas caminando', 'zancadas caminando', 'walking lunge'],
        ['estocadas con mancuernas', 'zancadas con mancuernas', 'dumbbell lunge'],
        ['estocadas con barra', 'zancadas con barra', 'barbell lunge'],
        ['estocadas hacia atrás', 'zancadas hacia atrás', 'reverse lunge'],
        ['sentadilla', 'sentadillas', 'squat', 'squats'],
        ['sentadilla con barra', 'barbell squat'],
        ['sentadilla sin peso', 'sentadilla con peso corporal', 'bodyweight squat'],
        ['sillón de cuádriceps', 'sillon de cuadriceps', 'extensión de cuádriceps',
            'extensiones de cuádriceps', 'extensión de piernas', 'lever leg extension', 'leg extension machine'],
        ['camilla de isquios', 'camilla de isquiotibiales', 'camilla de femorales',
            'curl femoral acostado', 'curl femoral tumbado', 'curl de piernas acostado',
            'lever lying leg curl', 'lying leg curl', 'prone leg curl'],
        ['búlgara', 'búlgaras', 'sentadilla búlgara', 'bulgarian split squat'],
        ['búlgara con mancuernas', 'sentadilla búlgara con mancuernas', 'dumbbell bulgarian split squat'],
        ['búlgara con barra', 'sentadilla búlgara con barra', 'barbell bulgarian split squat'],
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
                    $terms = array_merge($terms, $group['terms'], $group['search_only'] ?? []);
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
        foreach (self::IMPORT_EQUIVALENTS as $group) {
            $terms = array_map(self::normalize(...), $group);
            if (! in_array($normalized, $terms, true)) {
                continue;
            }
            $matches = $catalog->filter(fn (Exercise $exercise): bool => in_array(self::normalize($exercise->title), $terms, true));

            return $matches->count() === 1 ? $matches->first() : null;
        }

        return null;
    }
}
