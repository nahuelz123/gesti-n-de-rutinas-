<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use Illuminate\Http\Request;
use App\Models\Exercise;
use App\Models\ExerciseLog;

class RoutineController extends Controller
{
    public function active(Request $request)
    {
        $user = $request->user();

        $assignment = Assignment::query()
            ->with([
                'routine.days.exercises.exercise',
                // Solo logs de HOY para no llenar la vista
                'logs' => fn($q) => $q
                    ->whereDate('logged_at', today())
                    ->latest('logged_at'),
            ])
            ->where('client_id', $user->id)
            ->where('status', 'active')
            ->whereNull('end_date')
            ->latest('assigned_at')
            ->first();

        return view('client.routines.active', compact('assignment'));
    }

    public function history(Request $request)
    {
        $user = $request->user();

        $assignments = Assignment::query()
            ->with('routine')
            ->where('client_id', $user->id)
            ->latest('assigned_at')
            ->paginate(10);

        return view('client.routines.history', compact('assignments'));
    }

    public function show(Request $request, Assignment $assignment)
    {
        abort_unless($assignment->client_id === $request->user()->id, 404);

        $assignment->load(['routine.days.exercises.exercise']);

        return view('client.routines.show', compact('assignment'));
    }

    public function exerciseProgress(Request $request, Exercise $exercise)
    {
        $user = $request->user();

        // Todo el historial para el gráfico y la tabla de progreso
        $logs = ExerciseLog::query()
            ->whereHas('assignment', fn($q) => $q->where('client_id', $user->id))
            ->whereHas('routineDayExercise', fn($q) => $q->where('exercise_id', $exercise->id))
            ->orderByDesc('logged_at')
            ->take(100)
            ->get();

        $pr   = $logs->whereNotNull('weight')->max('weight');
        $last = $logs->first();

        return view('client.progress.exercise', compact('exercise', 'logs', 'pr', 'last'));
    }
}