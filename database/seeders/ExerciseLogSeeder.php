<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Assignment;

class ExerciseLogSeeder extends Seeder
{
    public function run(): void
    {
        $lucas  = User::where('email', 'lucas@cliente.com')->value('id');
        $sofia  = User::where('email', 'sofia@cliente.com')->value('id');

        // ── Logs de Lucas (assignment activa = Hipertrofia) ───────────────
        $lucasAssignment = Assignment::where('client_id', $lucas)
            ->where('status', 'active')
            ->first();

        if ($lucasAssignment) {
            $exercises = DB::table('routine_day_exercises')
                ->join('routine_days', 'routine_days.id', '=', 'routine_day_exercises.routine_day_id')
                ->where('routine_days.routine_id', $lucasAssignment->routine_id)
                ->select('routine_day_exercises.id', 'routine_day_exercises.sets')
                ->get();

            // Simula 4 semanas de entrenamiento con progresión de peso
            $weeks = 4;
            foreach ($exercises as $rde) {
                for ($week = $weeks; $week >= 1; $week--) {
                    $baseWeight = rand(50, 100);
                    $progression = ($weeks - $week) * rand(2, 5); // aumenta semana a semana
                    $date = now()->subWeeks($week)->addDays(rand(0, 4));

                    for ($set = 1; $set <= $rde->sets; $set++) {
                        DB::table('exercise_logs')->insert([
                            'assignment_id'           => $lucasAssignment->id,
                            'routine_day_exercise_id' => $rde->id,
                            'set_number'              => $set,
                            'weight'                  => $baseWeight + $progression,
                            'reps'                    => rand(8, 12),
                            'logged_at'               => $date->copy()->addMinutes($set * 3),
                            'created_at'              => now(),
                            'updated_at'              => now(),
                        ]);
                    }
                }
            }
        }

        // ── Logs de Sofía (Glúteos y Core) ───────────────────────────────
        $sofiaAssignment = Assignment::where('client_id', $sofia)
            ->where('status', 'active')
            ->first();

        if ($sofiaAssignment) {
            $exercises = DB::table('routine_day_exercises')
                ->join('routine_days', 'routine_days.id', '=', 'routine_day_exercises.routine_day_id')
                ->where('routine_days.routine_id', $sofiaAssignment->routine_id)
                ->select('routine_day_exercises.id', 'routine_day_exercises.sets')
                ->get();

            $weeks = 3;
            foreach ($exercises as $rde) {
                for ($week = $weeks; $week >= 1; $week--) {
                    $baseWeight = rand(20, 60);
                    $progression = ($weeks - $week) * rand(1, 3);
                    $date = now()->subWeeks($week)->addDays(rand(0, 4));

                    for ($set = 1; $set <= $rde->sets; $set++) {
                        DB::table('exercise_logs')->insert([
                            'assignment_id'           => $sofiaAssignment->id,
                            'routine_day_exercise_id' => $rde->id,
                            'set_number'              => $set,
                            'weight'                  => $baseWeight + $progression,
                            'reps'                    => rand(10, 15),
                            'logged_at'               => $date->copy()->addMinutes($set * 3),
                            'created_at'              => now(),
                            'updated_at'              => now(),
                        ]);
                    }
                }
            }
        }
    }
}