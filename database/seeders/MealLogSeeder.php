<?php

namespace Database\Seeders;

use App\Models\DietAssignment;
use App\Models\MealLog;
use App\Models\User;
use Illuminate\Database\Seeder;

class MealLogSeeder extends Seeder
{
    private array $dayMap = [
        1 => 'lunes', 2 => 'martes', 3 => 'miercoles', 4 => 'jueves',
        5 => 'viernes', 6 => 'sabado', 7 => 'domingo',
    ];

    public function run(): void
    {
        $todayKey = $this->dayMap[now()->dayOfWeekIso];

        // Lucas: marca solo el desayuno de hoy (progreso parcial, ~30%)
        $this->logPartialDay('lucas@cliente.com', $todayKey, 1);

        // Sofía: marca todas las comidas de hoy (día completo, 100%)
        $this->logFullDay('sofia@cliente.com', $todayKey);
    }

    private function assignmentFor(string $email): ?DietAssignment
    {
        $clientId = User::where('email', $email)->value('id');

        if (! $clientId) {
            return null;
        }

        return DietAssignment::with('dietPlan.days.recipes')
            ->where('client_id', $clientId)
            ->where('status', 'active')
            ->whereNull('end_date')
            ->latest('assigned_at')
            ->first();
    }

    private function logPartialDay(string $email, string $dayKey, int $howMany): void
    {
        $assignment = $this->assignmentFor($email);
        if (! $assignment) {
            return;
        }

        $day = $assignment->dietPlan?->days?->firstWhere('day_of_week', $dayKey);
        if (! $day) {
            return;
        }

        $day->recipes->take($howMany)->each(function ($dpr) use ($assignment) {
            MealLog::updateOrCreate(
                [
                    'diet_assignment_id' => $assignment->id,
                    'diet_plan_day_recipe_id' => $dpr->id,
                    'logged_date' => today(),
                ],
                [
                    'completed' => true,
                    'logged_at' => now(),
                ]
            );
        });
    }

    private function logFullDay(string $email, string $dayKey): void
    {
        $assignment = $this->assignmentFor($email);
        if (! $assignment) {
            return;
        }

        $day = $assignment->dietPlan?->days?->firstWhere('day_of_week', $dayKey);
        if (! $day) {
            return;
        }

        $day->recipes->each(function ($dpr) use ($assignment) {
            MealLog::updateOrCreate(
                [
                    'diet_assignment_id' => $assignment->id,
                    'diet_plan_day_recipe_id' => $dpr->id,
                    'logged_date' => today(),
                ],
                [
                    'completed' => true,
                    'logged_at' => now(),
                ]
            );
        });
    }
}
