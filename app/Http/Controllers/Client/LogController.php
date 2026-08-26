<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\ExerciseLog;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'assignment_id' => ['required','integer','exists:assignments,id'],
            'routine_day_exercise_id' => ['required','integer','exists:routine_day_exercises,id'],
            'set_number' => ['required','integer','min:1','max:20'],
            'weight' => ['nullable','numeric','min:0'],
            'reps' => ['nullable','integer','min:1','max:200'],
        ]);

        $assignment = Assignment::query()->findOrFail($data['assignment_id']);

        // 1) Solo dueño del assignment
        abort_unless($assignment->client_id === $user->id, 403);

        $isActive = $assignment->status === 'active' && $assignment->end_date === null;
        $isReplay = $assignment->status === 'completed' && 
                    $assignment->start_date !== null &&
                    \Carbon\Carbon::parse($assignment->start_date)->isToday() && 
                    $assignment->end_date !== null &&
                    \Carbon\Carbon::parse($assignment->end_date)->isToday();

        abort_unless($isActive || $isReplay, 403);

        // 3) Asegurar que ese routine_day_exercise pertenece a la rutina del assignment
        // (esto evita que un cliente mande un ID de otro day_exercise)
        $belongs = $assignment->routine
            ->days()
            ->whereHas('exercises', fn ($q) => $q->where('routine_day_exercises.id', $data['routine_day_exercise_id']))
            ->exists();

        abort_unless($belongs, 403);

        // Si ya había una serie cargada hoy con ese mismo número, la pisamos
        // en vez de crear un duplicado.
        $existing = ExerciseLog::query()
            ->where('assignment_id', $assignment->id)
            ->where('routine_day_exercise_id', $data['routine_day_exercise_id'])
            ->where('set_number', $data['set_number'])
            ->whereDate('logged_at', today())
            ->first();

        if ($existing) {
            $existing->update([
                'weight' => $data['weight'] ?? null,
                'reps' => $data['reps'] ?? null,
                'logged_at' => now(),
            ]);

            return back()->with('success', "Ya tenías cargada la serie {$data['set_number']} hoy, la actualicé con estos valores.");
        }

        ExerciseLog::create([
            'assignment_id' => $assignment->id,
            'routine_day_exercise_id' => $data['routine_day_exercise_id'],
            'set_number' => $data['set_number'],
            'weight' => $data['weight'] ?? null,
            'reps' => $data['reps'] ?? null,
            'logged_at' => now(),
        ]);

        return back()->with('success', 'Serie registrada.');
    }

    public function update(Request $request, ExerciseLog $log)
    {
        $user = $request->user();

        abort_unless($log->assignment->client_id === $user->id, 403);

        $data = $request->validate([
            'set_number' => ['required', 'integer', 'min:1', 'max:20'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'reps' => ['nullable', 'integer', 'min:1', 'max:200'],
            'logged_at' => ['nullable', 'date'],
        ]);

        $loggedAt = isset($data['logged_at']) ? \Carbon\Carbon::parse($data['logged_at']) : $log->logged_at;

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
