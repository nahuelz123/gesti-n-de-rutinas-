<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\NutritionCalculator;
use Illuminate\Http\Request;

class NutritionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $assignment = NutritionCalculator::activeAssignmentFor($user->id);

        $validDays = array_values(NutritionCalculator::$dayMap);
        $dayKey = $request->query('day', NutritionCalculator::todayKey());

        if (! in_array($dayKey, $validDays, true)) {
            $dayKey = NutritionCalculator::todayKey();
        }

        $day = $assignment ? NutritionCalculator::planDayFor($assignment, $dayKey) : null;
        $summary = ($assignment && $day) ? NutritionCalculator::summaryFor($assignment, $day) : null;

        $isToday = $dayKey === NutritionCalculator::todayKey();

        $grouped = $day ? $day->recipes->groupBy('meal_type') : collect();

        // El diario libre funciona tenga o no el cliente un plan de dieta activo.
        $free = NutritionCalculator::freeLogsToday($user->id);

        return view('client.nutrition.index', [
            'assignment' => $assignment,
            'day' => $day,
            'dayKey' => $dayKey,
            'isToday' => $isToday,
            'summary' => $summary,
            'grouped' => $grouped,
            'freeLogs' => $free['logs'],
            'freeTotals' => $free['totals'],
            'dayMap' => NutritionCalculator::$dayMap,
            'dayLabels' => NutritionCalculator::$dayLabels,
            'mealTypeOrder' => NutritionCalculator::$mealTypeOrder,
            'mealTypeLabels' => NutritionCalculator::$mealTypeLabels,
            'goalLabels' => NutritionCalculator::$goalLabels,
        ]);
    }
}
