<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\ExerciseLog;
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
            ->where(function ($query) {
                $query->where('status', '!=', 'active')
                    ->orWhereNotNull('end_date');
            })
            ->latest('assigned_at')
            ->take(10)
            ->get();

        $weeklyTarget = $active?->routine?->days?->count() ?? 0;
        $weeklyCompletedSessions = 0;
        $weeklyProgressPct = 0;
        $hasWorkoutToday = false;
        $nextWorkoutDay = null;

        if ($active) {
            $weekLogs = ExerciseLog::query()
                ->with('routineDayExercise.routineDay')
                ->where('assignment_id', $active->id)
                ->whereBetween('logged_at', [
                    now()->copy()->startOfWeek(),
                    now()->copy()->endOfWeek(),
                ])
                ->orderBy('logged_at')
                ->get();

            $weeklyCompletedSessions = $weekLogs
                ->groupBy(fn (ExerciseLog $log) => $log->logged_at->toDateString())
                ->count();

            if ($weeklyTarget > 0) {
                $weeklyCompletedSessions = min($weeklyCompletedSessions, $weeklyTarget);
                $weeklyProgressPct = min(100, (int) round(($weeklyCompletedSessions / $weeklyTarget) * 100));
            }

            $todayLogs = $weekLogs->filter(fn (ExerciseLog $log) => $log->logged_at->isToday());
            $hasWorkoutToday = $todayLogs->isNotEmpty();

            $latestActiveLog = ExerciseLog::query()
                ->with('routineDayExercise.routineDay')
                ->where('assignment_id', $active->id)
                ->latest('logged_at')
                ->first();

            $days = $active->routine->days->sortBy('day_number')->values();

            if ($hasWorkoutToday && $latestActiveLog?->routineDayExercise?->routineDay) {
                $nextWorkoutDay = $latestActiveLog->routineDayExercise->routineDay;
            } elseif ($latestActiveLog?->routineDayExercise?->routineDay && $days->isNotEmpty()) {
                $lastDayId = $latestActiveLog->routineDayExercise->routineDay->id;
                $lastIndex = $days->search(fn ($day) => $day->id === $lastDayId);
                $nextIndex = $lastIndex === false ? 0 : ($lastIndex + 1) % $days->count();
                $nextWorkoutDay = $days[$nextIndex];
            } else {
                $nextWorkoutDay = $days->first();
            }
        }

        $lastWorkout = ExerciseLog::query()
            ->whereHas('assignment', fn ($query) => $query->where('client_id', $user->id))
            ->latest('logged_at')
            ->first();

        // Nutrición: widget de hoy.
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
            'goalLabels',
            'weeklyTarget',
            'weeklyCompletedSessions',
            'weeklyProgressPct',
            'hasWorkoutToday',
            'nextWorkoutDay',
            'lastWorkout'
        ));
    }
}
