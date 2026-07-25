<?php

namespace App\Services;

use App\Models\DietAssignment;
use App\Models\DietPlanDay;
use App\Models\MealLog;

class NutritionCalculator
{
    public static array $dayMap = [
        1 => 'lunes', 2 => 'martes', 3 => 'miercoles', 4 => 'jueves',
        5 => 'viernes', 6 => 'sabado', 7 => 'domingo',
    ];

    public static array $dayLabels = [
        'lunes' => 'Lunes', 'martes' => 'Martes', 'miercoles' => 'Miércoles',
        'jueves' => 'Jueves', 'viernes' => 'Viernes', 'sabado' => 'Sábado', 'domingo' => 'Domingo',
    ];

    public static array $mealTypeOrder = [
        'desayuno', 'almuerzo', 'merienda', 'cena', 'pre_entrenamiento', 'post_entrenamiento',
    ];

    public static array $mealTypeLabels = [
        'desayuno' => 'Desayuno', 'almuerzo' => 'Almuerzo', 'merienda' => 'Merienda',
        'cena' => 'Cena', 'pre_entrenamiento' => 'Pre-entrenamiento', 'post_entrenamiento' => 'Post-entrenamiento',
    ];

    public static array $goalLabels = [
        'perdida_peso' => 'Pérdida de peso',
        'ganancia_muscular' => 'Ganancia muscular',
        'mantenimiento' => 'Mantenimiento',
        'rendimiento' => 'Rendimiento',
    ];

    public static function todayKey(): string
    {
        return static::$dayMap[now()->dayOfWeekIso];
    }

    public static function activeAssignmentFor(int $clientId): ?DietAssignment
    {
        return DietAssignment::query()
            ->with(['dietPlan.days.recipes.recipe'])
            ->where('client_id', $clientId)
            ->where('status', 'active')
            ->whereNull('end_date')
            ->latest('assigned_at')
            ->first();
    }

    public static function planDayFor(DietAssignment $assignment, string $dayKey): ?DietPlanDay
    {
        return $assignment->dietPlan?->days?->firstWhere('day_of_week', $dayKey);
    }

    public static function summaryFor(DietAssignment $assignment, DietPlanDay $day): array
    {
        $logs = MealLog::query()
            ->where('diet_assignment_id', $assignment->id)
            ->whereDate('logged_date', today())
            ->get()
            ->keyBy('diet_plan_day_recipe_id');

        $target = ['calories' => 0, 'protein' => 0, 'carbs' => 0, 'fat' => 0];
        $eaten = ['calories' => 0, 'protein' => 0, 'carbs' => 0, 'fat' => 0];

        // Agrupamos por meal_type: si hay varias opciones para la misma comida,
        // el objetivo usa el promedio de esas opciones (no se suman como si comiera todas).
        $groups = $day->recipes->groupBy('meal_type');

        foreach ($groups as $options) {
            $validOptions = $options->filter(fn ($dpr) => $dpr->recipe !== null);
            $count = $validOptions->count();

            if ($count === 0) continue;

            foreach ($validOptions as $dpr) {
                $servings = (float) $dpr->servings;
                $recipe = $dpr->recipe;

                $target['calories'] += (($recipe->calories ?? 0) * $servings) / $count;
                $target['protein'] += (($recipe->protein ?? 0) * $servings) / $count;
                $target['carbs'] += (($recipe->carbs ?? 0) * $servings) / $count;
                $target['fat'] += (($recipe->fat ?? 0) * $servings) / $count;
            }

            // Comido = la opción de este grupo que el cliente marcó como hecha (si eligió alguna)
            $chosen = $validOptions->first(fn ($dpr) => optional($logs->get($dpr->id))->completed);

            if ($chosen) {
                $log = $logs->get($chosen->id);
                $servings = (float) $chosen->servings;
                $eatenServings = $log->servings_eaten !== null ? (float) $log->servings_eaten : $servings;
                $recipe = $chosen->recipe;

                $eaten['calories'] += ($recipe->calories ?? 0) * $eatenServings;
                $eaten['protein'] += ($recipe->protein ?? 0) * $eatenServings;
                $eaten['carbs'] += ($recipe->carbs ?? 0) * $eatenServings;
                $eaten['fat'] += ($recipe->fat ?? 0) * $eatenServings;
            }
        }

        $mealsTotal = $groups->count(); // cada meal_type cuenta como UNA comida, tenga 1 o varias opciones
        $mealsDone = $groups->filter(
            fn ($options) => $options->contains(fn ($dpr) => optional($logs->get($dpr->id))->completed)
        )->count();

        return [
            'target' => $target,
            'eaten' => $eaten,
            'logs' => $logs,
            'meals_total' => $mealsTotal,
            'meals_done' => $mealsDone,
        ];
    }
}
