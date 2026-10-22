<?php

namespace App\Support;

use App\Models\Exercise;

class MuscleMap
{
    private const GROUP_ALIASES = [
        'pecho' => 'chest', 'pectorales' => 'chest', 'pectoral' => 'chest',
        'espalda' => 'back', 'dorsales' => 'back',
        'hombros' => 'shoulders', 'hombro' => 'shoulders', 'deltoides' => 'shoulders',
        'biceps' => 'biceps', 'triceps' => 'triceps',
        'antebrazos' => 'forearms', 'antebrazo' => 'forearms',
        'abdomen' => 'abs', 'abdominales' => 'abs',
        'gluteos' => 'glutes', 'gluteo' => 'glutes',
        'cuadriceps' => 'quadriceps',
        'isquiotibiales' => 'hamstrings', 'isquios' => 'hamstrings', 'femorales' => 'hamstrings',
        'gemelos' => 'calves', 'pantorrillas' => 'calves',
    ];

    private const SQUATS_AND_LUNGES = [
        'sentadilla', 'sentadillas', 'sentadilla profunda', 'sentadilla con barra',
        'sentadilla frontal', 'barbell squat', 'bodyweight squat', 'squat', 'squats',
        'sentadilla bulgara', 'bulgara', 'bulgaras', 'bulgarian split squat',
        'sentadilla bulgara con mancuernas', 'dumbbell bulgarian split squat',
        'sentadilla bulgara con barra', 'barbell bulgarian split squat',
        'estocada', 'estocadas', 'zancada', 'zancadas', 'lunge', 'lunges',
        'zancadas caminando', 'estocadas caminando', 'walking lunge',
        'estocadas con mancuernas', 'zancadas con mancuernas', 'dumbbell lunge',
        'estocadas con barra', 'zancadas con barra', 'barbell lunge',
        'estocadas hacia atras', 'zancadas hacia atras', 'reverse lunge',
    ];

    private const LEG_EXTENSIONS = [
        'sillon de cuadriceps', 'extension de cuadriceps', 'extensiones de cuadriceps',
        'extension de piernas', 'extensiones de piernas', 'lever leg extension',
        'leg extension machine',
    ];

    private const LEG_PRESSES = [
        'prensa', 'prensa de piernas', 'prensa unilateral',
    ];

    private const ROMANIAN_DEADLIFTS = ['peso muerto rumano', 'romanian deadlift'];

    private const LEG_CURLS = [
        'camilla de isquios', 'camilla de isquiotibiales', 'camilla de femorales',
        'curl femoral', 'curl femoral acostado', 'curl femoral tumbado',
        'curl de piernas acostado', 'lever lying leg curl', 'lying leg curl', 'prone leg curl',
    ];

    private const CALF_RAISES = [
        'gemelos', 'elevacion de talones', 'elevaciones de talones',
        'calf raise', 'calf raises', 'standing calf raise', 'standing calf raises',
    ];

    private const CHEST_PRESSES = [
        'press banca', 'press banca con barra', 'barbell bench press', 'bench press',
        'press banca con mancuernas', 'dumbbell bench press',
        'press banca plano', 'press banca en maquina', 'press pecho plano en maquina',
        'press pecho en maquina', 'press plano en maquina', 'plano en maquina',
        'press inclinado', 'press inclinado con barra', 'press inclinado barra',
        'press inclinado con mancuernas', 'press inclinado mancuernas',
        'press inclinado en maquina', 'press inclinado en smith',
        'press plano en smith', 'press plano en smit', 'plano en smith', 'plano en smit',
        'smith bench press', 'smith incline bench press', 'lever chest press',
        'lever incline chest press', 'barbell incline bench press', 'dumbbell incline bench press',
    ];

    private const SHOULDER_PRESSES = [
        'press militar', 'press militar con barra', 'press militar con mancuernas',
        'press militar mancuernas', 'press militar en maquina', 'press militar en smith',
        'press militar en smit', 'press de hombros', 'press de hombros en maquina',
        'press hombros en maquina', 'dumbbell shoulder press', 'lever shoulder press',
        'smith shoulder press', 'smith standing military press', 'military press',
    ];

    private const BACK_PULLS = [
        'jalon al pecho', 'jalon al pecho agarre neutro', 'lat pulldown',
        'remo con barra', 'remo barra', 'remo en maquina', 'remo maquina',
        'remo t', 'remo en t', 'remo unilateral', 'remo polea', 'remo en polea',
        'remo con mancuerna', 'remo mancuerna', 'cable seated row',
        'lever t bar row', 'barbell bent over row',
    ];

    private const CLOSE_GRIP_PRESSES = ['press cerrado', 'press cerrado con barra', 'press plano cerrado'];

    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            'chest' => 'Pecho', 'back' => 'Espalda', 'shoulders' => 'Hombros',
            'biceps' => 'Bíceps', 'triceps' => 'Tríceps', 'forearms' => 'Antebrazos',
            'abs' => 'Abdomen', 'glutes' => 'Glúteos', 'quadriceps' => 'Cuádriceps',
            'hamstrings' => 'Isquiotibiales', 'calves' => 'Pantorrillas',
        ];
    }

    /** @return array{primary: list<string>, secondary: list<string>, label: string, available: bool} */
    public static function forExercise(Exercise $exercise): array
    {
        $group = ExerciseNames::normalize((string) $exercise->muscle_group);
        $title = ExerciseNames::normalize((string) $exercise->title);
        $labels = self::labels();
        $primary = isset($labels[$group]) ? $group : (self::GROUP_ALIASES[$group] ?? null);

        if (in_array($group, ['piernas', 'pierna', 'legs'], true)) {
            $primary = match (true) {
                in_array($title, self::SQUATS_AND_LUNGES, true),
                in_array($title, self::LEG_EXTENSIONS, true),
                in_array($title, self::LEG_PRESSES, true) => 'quadriceps',
                in_array($title, self::LEG_CURLS, true),
                in_array($title, self::ROMANIAN_DEADLIFTS, true) => 'hamstrings',
                in_array($title, self::CALF_RAISES, true) => 'calves',
                default => null,
            };
        }

        if ($primary === null) {
            return [
                'primary' => [], 'secondary' => [],
                'label' => 'Mapa muscular no disponible', 'available' => false,
            ];
        }

        // Complete curated names only; sources and editorial limits live in docs/muscle-sources.md.
        $secondary = match (true) {
            $primary === 'chest' && in_array($title, self::CHEST_PRESSES, true) => ['shoulders', 'triceps'],
            $primary === 'chest' && in_array($title, self::CLOSE_GRIP_PRESSES, true) => ['shoulders', 'triceps'],
            $primary === 'shoulders' && in_array($title, self::SHOULDER_PRESSES, true) => ['triceps'],
            $primary === 'back' && in_array($title, self::BACK_PULLS, true) => ['biceps'],
            $primary === 'triceps' && in_array($title, self::CLOSE_GRIP_PRESSES, true) => ['chest', 'shoulders'],
            $primary === 'quadriceps' && in_array($title, self::SQUATS_AND_LUNGES, true) => ['glutes'],
            $primary === 'hamstrings' && in_array($title, self::ROMANIAN_DEADLIFTS, true) => ['glutes'],
            $primary === 'glutes' && in_array($title, self::ROMANIAN_DEADLIFTS, true) => ['hamstrings'],
            default => [],
        };

        return [
            'primary' => [$primary], 'secondary' => $secondary,
            'label' => $labels[$primary], 'available' => true,
        ];
    }

    public static function combine(array $maps, string $label): array
    {
        $primary = array_merge([], ...array_column($maps, 'primary'));
        $secondary = array_merge([], ...array_column($maps, 'secondary'));
        $known = array_keys(self::labels());
        $primary = array_values(array_intersect($known, $primary));
        $secondary = array_values(array_diff(array_intersect($known, $secondary), $primary));

        return ['primary' => $primary, 'secondary' => $secondary, 'label' => $label,
            'available' => $primary !== [] || $secondary !== []];
    }
}
