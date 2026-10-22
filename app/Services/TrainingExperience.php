<?php

namespace App\Services;

use App\Models\Exercise;
use App\Models\User;
use App\Support\MuscleMap;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

class TrainingExperience
{
    public function forClient(User $client): array
    {
        $timezone = config('app.timezone');
        $now = CarbonImmutable::now($timezone);
        $today = $now->toDateString();
        $thisWeek = $now->format('o-W');
        $dates = [];
        $weeks = [];
        $weekDates = [];
        $muscleSets = [];
        $maps = [];
        $recent = [];
        $totalSets = 0;
        $todaySets = 0;
        $todayExercises = [];

        // Joining includes archived assignments/exercises without loading a relation for every set.
        // COALESCE falls back only for legacy logs whose performed exercise was not stored.
        $logs = DB::table('exercise_logs')
            ->join('assignments', 'assignments.id', '=', 'exercise_logs.assignment_id')
            ->leftJoin('routine_day_exercises', 'routine_day_exercises.id', '=', 'exercise_logs.routine_day_exercise_id')
            ->leftJoin('exercises', function (JoinClause $join): void {
                $join->on('exercises.id', '=', DB::raw('COALESCE(exercise_logs.exercise_id, routine_day_exercises.exercise_id)'));
            })
            ->where('assignments.client_id', $client->id)
            ->where('exercise_logs.reps', '>', 0)
            ->where(function ($query): void {
                $query->whereNull('exercise_logs.weight')->orWhere('exercise_logs.weight', '>=', 0);
            })
            ->where('exercise_logs.logged_at', '<=', $now->toDateTimeString())
            ->select([
                'exercise_logs.reps', 'exercise_logs.weight', 'exercise_logs.logged_at',
                'exercises.id as exercise_id', 'exercises.title', 'exercises.muscle_group',
            ])
            ->orderByDesc('exercise_logs.logged_at')
            ->orderByDesc('exercise_logs.id')
            ->cursor();

        foreach ($logs as $log) {
            $loggedAt = $this->localDate((string) $log->logged_at, $timezone);
            if ($loggedAt === null || $loggedAt->greaterThan($now)) {
                continue;
            }

            $date = $loggedAt->toDateString();
            $week = $loggedAt->format('o-W');
            $dates[$date] = true;
            $weeks[$week] = true;
            $totalSets++;
            $todaySets += (int) ($date === $today);
            if ($week === $thisWeek) {
                $weekDates[$date] = true;
            }

            if ($log->exercise_id !== null) {
                $exerciseId = (int) $log->exercise_id;
                $map = $maps[$exerciseId] ??= MuscleMap::forExercise(new Exercise([
                    'title' => $log->title, 'muscle_group' => $log->muscle_group,
                ]));
                if ($date === $today) {
                    $todayExercises[$exerciseId] = ['id' => 'exercise-'.$exerciseId, 'title' => $log->title] + $map;
                }
                foreach (array_unique([...$map['primary'], ...$map['secondary']]) as $muscle) {
                    $muscleSets[$muscle] = ($muscleSets[$muscle] ?? 0) + 1;
                }

                // Only the presentation list is limited; progression processes the entire cursor.
                if (count($recent) < 8) {
                    $recent[] = [
                        'id' => $exerciseId, 'title' => $log->title,
                        'weight' => $log->weight === null ? null : (float) $log->weight,
                        'reps' => (int) $log->reps, 'logged_at' => $loggedAt->toIso8601String(),
                    ];
                }
            }
        }

        $muscles = [];
        $muscleActivity = [];
        foreach (MuscleMap::labels() as $id => $label) {
            if (isset($muscleSets[$id])) {
                $muscles[] = $id;
                $muscleActivity[] = ['id' => $id, 'label' => $label, 'sets' => $muscleSets[$id]];
            }
        }
        $activeDays = count($dates);
        $activeWeeks = count($weeks);
        $xp = $activeDays * 100;
        $levelXp = $xp % 500;

        return [
            'xp' => $xp,
            'level' => 1 + intdiv($xp, 500),
            'level_xp' => $levelXp,
            'next_level_xp' => 500,
            'level_percent' => (int) ($levelXp / 5),
            'active_days' => $activeDays,
            'active_weeks' => $activeWeeks,
            'total_sets' => $totalSets,
            'today_sets' => $todaySets,
            'week_days' => count($weekDates),
            'muscles' => $muscles,
            'muscle_activity' => $muscleActivity,
            'achievements' => $this->achievements($activeDays, $activeWeeks, count($muscles)),
            'recent_exercises' => $recent,
            'today_map' => MuscleMap::combine(array_values($todayExercises), 'Entrenamiento de hoy'),
            'today_exercises' => array_values($todayExercises),
        ];
    }

    private function localDate(string $timestamp, string $timezone): ?CarbonImmutable
    {
        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d H:i:s', $timestamp, $timezone);
        } catch (\InvalidArgumentException) {
            return null;
        }

        // Reject malformed legacy dates that PHP would otherwise normalize, such as February 30.
        return $date && $date->toDateTimeString() === $timestamp ? $date : null;
    }

    private function achievements(int $days, int $weeks, int $muscles): array
    {
        $milestones = [
            ['first_day', 'Primer registro', 'Registrá actividad en tu primer día.', $days, 1],
            ['five_days', 'Cinco días activos', 'Registrá actividad en 5 días distintos.', $days, 5],
            ['twenty_days', 'Veinte días activos', 'Registrá actividad en 20 días distintos.', $days, 20],
            ['four_weeks', 'Cuatro semanas activas', 'Registrá actividad en 4 semanas distintas.', $weeks, 4],
            ['five_muscles', 'Cinco grupos musculares', 'Registrá ejercicios de 5 grupos musculares conocidos.', $muscles, 5],
        ];

        return array_map(fn (array $milestone): array => [
            'id' => $milestone[0], 'title' => $milestone[1], 'description' => $milestone[2],
            'unlocked' => $milestone[3] >= $milestone[4],
            'current' => min($milestone[3], $milestone[4]), 'target' => $milestone[4],
        ], $milestones);
    }
}
