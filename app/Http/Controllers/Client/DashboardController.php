<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Services\NutritionCalculator;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $active = Assignment::query()
            ->with(['routine.days.exercises.exercise'])
            ->where('client_id', $user->id)
            ->where('status', 'active')
            ->whereNull('end_date')
            ->latest('assigned_at')
            ->first();

        $history = Assignment::query()
            ->with('routine')
            ->where('client_id', $user->id)
            ->where(function ($q) {
                $q->where('status', '!=', 'active')
                  ->orWhereNotNull('end_date');
            })
            ->latest('assigned_at')
            ->take(10)
            ->get();

        // Nutrición: widget de hoy
        $dietAssignment = NutritionCalculator::activeAssignmentFor($user->id);
        $todayDietDay = $dietAssignment
            ? NutritionCalculator::planDayFor($dietAssignment, NutritionCalculator::todayKey())
            : null;
        $nutritionSummary = ($dietAssignment && $todayDietDay)
            ? NutritionCalculator::summaryFor($dietAssignment, $todayDietDay)
            : null;
        $goalLabels = NutritionCalculator::$goalLabels;

        return view('client.dashboard', compact(
            'active',
            'history',
            'dietAssignment',
            'todayDietDay',
            'nutritionSummary',
            'goalLabels'
        ));
    }
}
