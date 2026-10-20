<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\ExerciseLog;
use App\Services\ExerciseSetRecorder;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function store(Request $request, ExerciseSetRecorder $recorder)
    {
        $user = $request->user();

        $data = $request->validate([
            'assignment_id' => ['required', 'integer', 'exists:assignments,id'],
            'routine_day_exercise_id' => ['required', 'integer', 'exists:routine_day_exercises,id'],
            'set_number' => ['required', 'integer', 'min:1', 'max:20'],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'reps' => ['nullable', 'integer', 'min:1', 'max:100'],
            'expected_exercise_id' => ['nullable', 'required_with:performed_on', 'integer', 'min:1'],
            'performed_on' => ['nullable', 'required_with:expected_exercise_id', 'date_format:Y-m-d'],
        ]);

        $assignment = Assignment::query()->findOrFail($data['assignment_id']);

        $updated = $recorder->record(
            $assignment,
            $user,
            (int) $data['routine_day_exercise_id'],
            (int) $data['set_number'],
            isset($data['weight']) ? (float) $data['weight'] : null,
            isset($data['reps']) ? (int) $data['reps'] : null,
            isset($data['expected_exercise_id']) ? (int) $data['expected_exercise_id'] : null,
            $data['performed_on'] ?? null,
        );

        return back()->with('success', $updated
            ? "Ya tenías cargada la serie {$data['set_number']} hoy, la actualicé con estos valores."
            : 'Serie registrada.');
    }

    public function update(Request $request, ExerciseLog $log)
    {
        $user = $request->user();

        abort_unless($log->assignment->client_id === $user->id, 403);

        $data = $request->validate([
            'set_number' => ['required', 'integer', 'min:1', 'max:20'],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'reps' => ['nullable', 'integer', 'min:1', 'max:100'],
            'logged_at' => ['nullable', 'date'],
        ]);

        $loggedAt = isset($data['logged_at']) ? Carbon::parse($data['logged_at']) : $log->logged_at;

        // No dejamos que la edición choque con otra serie del mismo día/ejercicio.
        $duplicate = ExerciseLog::query()
            ->where('id', '!=', $log->id)
            ->where('assignment_id', $log->assignment_id)
            ->where('routine_day_exercise_id', $log->routine_day_exercise_id)
            ->where('set_number', $data['set_number'])
            ->whereDate('logged_at', $loggedAt->toDateString())
            ->exists();

        if ($duplicate) {
            return back()->withErrors([
                'set_number' => "Ya existe otra serie {$data['set_number']} en esa fecha para este ejercicio. Elegí otro número o borrá la otra primero.",
            ]);
        }

        $log->update([
            'set_number' => $data['set_number'],
            'weight' => $data['weight'] ?? null,
            'reps' => $data['reps'] ?? null,
            'logged_at' => $loggedAt,
        ]);

        return back()->with('success', 'Serie actualizada.');
    }

    public function destroy(Request $request, ExerciseLog $log)
    {
        abort_unless($log->assignment->client_id === $request->user()->id, 403);

        $log->delete();

        return back()->with('success', 'Serie eliminada.');
    }
}
