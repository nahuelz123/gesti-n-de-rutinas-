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

        // 2) Solo rutina activa (como pediste)
        abort_unless($assignment->status === 'active' && $assignment->end_date === null, 403);

        // 3) Asegurar que ese routine_day_exercise pertenece a la rutina del assignment
        // (esto evita que un cliente mande un ID de otro day_exercise)
        $belongs = $assignment->routine
            ->days()
            ->whereHas('exercises', fn ($q) => $q->where('routine_day_exercises.id', $data['routine_day_exercise_id']))
            ->exists();

        abort_unless($belongs, 403);

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
}
