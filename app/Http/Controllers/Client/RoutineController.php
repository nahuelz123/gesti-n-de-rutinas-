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

    public function replay(Request $request, Assignment $assignment)
    {
        $user = $request->user();
        abort_unless($assignment->client_id === $user->id, 404);

        $replayAssignment = Assignment::query()
            ->where('client_id', $user->id)
            ->where('routine_id', $assignment->routine_id)
            ->whereDate('start_date', today())
            ->whereNotNull('end_date')
            ->first();

        if (! $replayAssignment) {
            $replayAssignment = Assignment::create([
                'gym_id' => $assignment->gym_id,
                'routine_id' => $assignment->routine_id,
                'client_id' => $user->id,
                'assigned_by_id' => $assignment->assigned_by_id,
                'assigned_at' => now(),
                'start_date' => today(),
                'end_date' => today(),
                'status' => 'completed',
                'notes' => 'Sesión iniciada desde historial',
            ]);
        }

        return redirect()->route('client.routines.session', $replayAssignment);
    }

    public function session(Request $request, Assignment $assignment)
    {
        $user = $request->user();
        abort_unless($assignment->client_id === $user->id, 404);

        // Allow viewing only if it's a valid replay session
        abort_unless($assignment->end_date !== null && \Carbon\Carbon::parse($assignment->start_date)->isToday(), 403);

        $assignment->load(['routine.days.exercises.exercise']);

        return view('client.routines.session', compact('assignment'));
    }
}
