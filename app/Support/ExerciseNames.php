<?php

namespace App\Support;

use App\Models\Exercise;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ExerciseNames
{
    private const GROUPS = [
        ['label' => 'Press plano en Smith', 'terms' => [
            'plano en smit', 'plano en smith', 'press plano smit', 'press plano smith',
            'press plano en smit', 'press plano en smith', 'press banca plano smith', 'Smith bench press',
        ]],
        ['label' => 'Press militar en Smith', 'terms' => [
            'press militar en smit', 'press militar en smith', 'press militar smith',
            'Smith standing military press', 'Smith shoulder press',
        ]],
        ['label' => 'JM press', 'terms' => ['JM press', 'J press', 'KAS press', 'Kass press']],
        ['label' => 'Elevaciones laterales', 'terms' => [
            'vuelos laterales', 'vuelo lateral', 'elevaciones laterales',
            'Dumbbell lateral raise', 'Cable lateral raise', 'Lever lateral raise',
        ]],
        ['label' => 'Press pecho plano en máquina', 'terms' => [
            'plano en maquina', 'plano en máquina', 'press plano en maquina',
            'press plano en máquina', 'Press pecho plano en máquina',
        ]],
        ['label' => 'Press cerrado con barra', 'terms' => [
            'press cerrado', 'press plano cerrado', 'Press cerrado con barra',
        ]],
        ['label' => 'Tríceps katana bilateral', 'terms' => [
            'katana a dos brazos', 'triceps katana', 'tríceps katana', 'Tríceps katana bilateral',
        ]],
        ['label' => 'Press militar en máquina', 'terms' => [
            'press militar en maquina', 'press militar en máquina', 'Press militar en máquina',
        ]],
        ['label' => 'Estocadas', 'terms' => [
            'estocada', 'estocadas', 'zancada', 'zancadas', 'lunge', 'lunges',
            'Zancadas caminando', 'Estocadas caminando', 'Walking lunge',
            'Barbell lunge', 'Dumbbell lunge', 'Reverse lunge',
        ]],
        ['label' => 'Sentadilla', 'terms' => [
            'sentadilla', 'sentadillas', 'sentadilla profunda', 'squat', 'squats',
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
        ['sentadilla', 'sentadillas', 'sentadilla profunda', 'squat', 'squats'],
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

    // Alias del gym con destino concreto: evitan confundir barra, mancuernas, Smith y máquina.
    private const IMPORT_PREFERRED = [
        ['title' => 'Smith bench press', 'aliases' => [
            'plano en smit', 'plano en smith', 'press plano smit', 'press plano smith',
            'press plano en smit', 'press plano en smith', 'press banca plano smith',
        ]],
        ['title' => 'Press militar en Smith', 'aliases' => [
            'press militar en smit', 'press militar en smith', 'press militar smith',
        ]],
        ['title' => 'JM press', 'aliases' => ['j press', 'kas press', 'kass press']],
        ['title' => 'Elevaciones laterales', 'aliases' => ['vuelos laterales', 'vuelo lateral']],
        ['title' => 'Press pecho plano en máquina', 'aliases' => [
            'plano en maquina', 'press plano en maquina',
        ]],
        ['title' => 'Press militar en máquina', 'aliases' => ['press militar en maquina']],
        ['title' => 'Press cerrado con barra', 'aliases' => ['press cerrado', 'press plano cerrado']],
        ['title' => 'Tríceps katana bilateral', 'aliases' => ['katana a dos brazos', 'triceps katana']],
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
        foreach (self::IMPORT_PREFERRED as $preferred) {
            if (! in_array($normalized, array_map(self::normalize(...), $preferred['aliases']), true)) {
                continue;
            }
            $matches = $catalog->filter(fn (Exercise $exercise): bool => self::normalize($exercise->title) === self::normalize($preferred['title']));

            return $matches->count() === 1 ? $matches->first() : null;
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
