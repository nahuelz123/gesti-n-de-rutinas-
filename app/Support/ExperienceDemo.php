<?php

namespace App\Support;

use App\Models\Exercise;

class ExperienceDemo
{
    public static function context(): array
    {
        $exercises = collect([
            ['id' => 'press', 'title' => 'Press banca en máquina', 'muscle_group' => 'Pecho'],
            ['id' => 'squat', 'title' => 'Sentadilla', 'muscle_group' => 'Piernas'],
            ['id' => 'row', 'title' => 'Remo en máquina', 'muscle_group' => 'Espalda'],
            ['id' => 'curl', 'title' => 'Curl de bíceps', 'muscle_group' => 'Bíceps'],
            ['id' => 'lateral', 'title' => 'Vuelos laterales', 'muscle_group' => 'Hombros'],
            ['id' => 'unknown', 'title' => 'Ejercicio sin grupo cargado', 'muscle_group' => ''],
        ])->map(fn ($item) => ['id' => $item['id'], 'title' => $item['title']] + MuscleMap::forExercise(new Exercise($item)))->all();

        return [
            'isDemo' => true,
            'displayName' => 'Alex Vega',
            'avatarStorageKey' => 'visionfit:avatar:demo',
            'trainingUrl' => route('experience.demo').'#vf-demo-lab',
            'progressUrl' => route('experience.demo.character').'#vf-character-history',
            'characterUrl' => route('experience.demo.character'),
            'nutritionUrl' => route('login'),
            'workoutTitle' => 'Torso A · Pecho y brazos',
            'workoutSubtitle' => 'Una rutina de ejemplo para explorar el nuevo diseño.',
            'workoutCompleted' => false,
            'hasAssignedRoutine' => true,
            'nextExercise' => ['title' => 'Press banca en máquina', 'sets' => 3, 'reps' => '8-10', 'rest' => '90'],
            'initialMuscleMap' => array_intersect_key($exercises[0], array_flip(['primary', 'secondary', 'label', 'available'])),
            'demoExercises' => $exercises,
            'weekActivity' => [
                ['label' => 'L', 'active' => true, 'today' => false],
                ['label' => 'M', 'active' => true, 'today' => true],
                ['label' => 'X', 'active' => false, 'today' => false],
                ['label' => 'J', 'active' => false, 'today' => false],
                ['label' => 'V', 'active' => false, 'today' => false],
                ['label' => 'S', 'active' => false, 'today' => false],
                ['label' => 'D', 'active' => false, 'today' => false],
            ],
            'experience' => [
                'xp' => 1200, 'level' => 3, 'level_xp' => 200, 'next_level_xp' => 500,
                'level_percent' => 40, 'active_days' => 12, 'active_weeks' => 4,
                'total_sets' => 144, 'today_sets' => 3, 'week_days' => 2,
                'muscles' => ['chest', 'back', 'shoulders', 'biceps', 'triceps', 'quadriceps'],
                'muscle_activity' => [
                    ['id' => 'chest', 'label' => 'Pecho', 'sets' => 28],
                    ['id' => 'back', 'label' => 'Espalda', 'sets' => 25],
                    ['id' => 'quadriceps', 'label' => 'Cuádriceps', 'sets' => 24],
                    ['id' => 'shoulders', 'label' => 'Hombros', 'sets' => 22],
                    ['id' => 'biceps', 'label' => 'Bíceps', 'sets' => 22],
                    ['id' => 'triceps', 'label' => 'Tríceps', 'sets' => 23],
                ],
                'achievements' => [
                    ['id' => 'first', 'title' => 'Primera huella', 'description' => 'Tu primer día con actividad registrada.', 'unlocked' => true, 'current' => 1, 'target' => 1],
                    ['id' => 'rhythm', 'title' => 'En ritmo', 'description' => 'Registrá actividad en 5 días distintos.', 'unlocked' => true, 'current' => 5, 'target' => 5],
                    ['id' => 'habit', 'title' => 'Paso a paso', 'description' => 'Sumá 20 días con actividad registrada.', 'unlocked' => false, 'current' => 12, 'target' => 20],
                    ['id' => 'weeks', 'title' => 'Tu recorrido', 'description' => 'Tené actividad en 4 semanas distintas.', 'unlocked' => true, 'current' => 4, 'target' => 4],
                    ['id' => 'explorer', 'title' => 'Explorador', 'description' => 'Registrá ejercicios de 5 grupos musculares.', 'unlocked' => true, 'current' => 5, 'target' => 5],
                ],
                'recent_exercises' => [
                    ['id' => 1, 'title' => 'Press banca en máquina', 'weight' => 40, 'reps' => 10, 'logged_at' => '2026-10-20T12:30:00-03:00'],
                    ['id' => 2, 'title' => 'Cruce en polea', 'weight' => 15, 'reps' => 12, 'logged_at' => '2026-10-20T12:35:00-03:00'],
                    ['id' => 3, 'title' => 'Fondos', 'weight' => null, 'reps' => 10, 'logged_at' => '2026-10-20T12:40:00-03:00'],
                ],
            ],
        ];
    }
}
