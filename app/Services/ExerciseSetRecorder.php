<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Exercise;
use App\Models\ExerciseLog;
use App\Models\RoutineDayExercise;
use App\Models\User;
use App\Models\WorkoutExerciseReplacement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExerciseSetRecorder
{
    /**
     * Save a set safely from either the HTTP endpoint or the Livewire logger.
     *
     * @return bool Whether an existing set was replaced.
     */
    public function record(
        Assignment $assignment,
        User $client,
        int $routineDayExerciseId,
        int $setNumber,
        ?float $weight,
        ?int $reps,
        ?int $expectedExerciseId = null,
        ?string $performedOn = null
    ): bool {
        $performedOn ??= today()->toDateString();

        return DB::transaction(function () use ($assignment, $client, $routineDayExerciseId, $setNumber, $weight, $reps, $expectedExerciseId, $performedOn): bool {
            // Serializing writes by assignment prevents duplicate sets during rapid submissions.
            $lockedAssignment = Assignment::query()->lockForUpdate()->findOrFail($assignment->id);

            abort_unless($lockedAssignment->client_id === $client->id, 403);
            abort_unless($this->isEditableSession($lockedAssignment), 403);

            $exercise = RoutineDayExercise::query()
                ->whereKey($routineDayExerciseId)
                ->whereHas('routineDay', fn ($query) => $query->where('routine_id', $lockedAssignment->routine_id))
                ->first();

            abort_unless($exercise, 403);
            abort_unless($setNumber >= 1 && $setNumber <= min(20, (int) $exercise->sets), 422);

            $existing = ExerciseLog::query()
                ->where('assignment_id', $lockedAssignment->id)
                ->where('routine_day_exercise_id', $exercise->id)
                ->where('set_number', $setNumber)
                ->whereBetween('logged_at', [today()->startOfDay(), today()->endOfDay()])
                ->first();

            $firstLog = ExerciseLog::query()->where('assignment_id', $lockedAssignment->id)
                ->where('routine_day_exercise_id', $exercise->id)->whereDate('logged_at', today())->first();
            $actualExerciseId = $firstLog ? ($firstLog->exercise_id ?? $exercise->exercise_id)
                : (WorkoutExerciseReplacement::query()->where('assignment_id', $lockedAssignment->id)
                    ->where('routine_day_exercise_id', $exercise->id)->whereDate('performed_on', today())->value('exercise_id') ?? $exercise->exercise_id);
            // Legacy HTTP callers describe the original prescription, never an unseen replacement.
            $expectedExerciseId ??= $exercise->exercise_id;
            if ($performedOn !== today()->toDateString() || (int) $expectedExerciseId !== (int) $actualExerciseId) {
                throw ValidationException::withMessages(['workout_context' => 'Cambió el ejercicio o la fecha de esta sesión. Actualizamos el formulario. Volvé a ingresar la serie para el ejercicio que aparece ahora.']);
            }

            $values = ['weight' => $weight, 'reps' => $reps, 'logged_at' => now()];

            if ($existing !== null) {
                $existing->update($values);

                return true;
            }

            ExerciseLog::create($values + [
                'assignment_id' => $lockedAssignment->id,
                'routine_day_exercise_id' => $exercise->id,
                'set_number' => $setNumber,
                'exercise_id' => $actualExerciseId,
            ]);

            return false;
        });
    }

    public function replace(Assignment $assignment, User $client, int $routineDayExerciseId, int $exerciseId): void
    {
        DB::transaction(function () use ($assignment, $client, $routineDayExerciseId, $exerciseId): void {
            // Use the same lock as record(): a racing save cannot change its exercise.
            $locked = Assignment::query()->lockForUpdate()->findOrFail($assignment->id);
            abort_unless($locked->client_id === $client->id && $this->isEditableSession($locked), 403);
            $slot = RoutineDayExercise::query()->whereKey($routineDayExerciseId)
                ->whereHas('routineDay', fn ($query) => $query->where('routine_id', $locked->routine_id))->first();
            abort_unless($slot, 403);
            abort_unless(Exercise::query()->visibleToGym($client->gym_id)->whereKey($exerciseId)->exists(), 403);

            if (ExerciseLog::query()->where('assignment_id', $locked->id)
                ->where('routine_day_exercise_id', $slot->id)->whereDate('logged_at', today())->exists()) {
                throw ValidationException::withMessages(['replacement' => 'Ya guardaste series. El ejercicio no se puede cambiar para no alterar tu historial.']);
            }

            WorkoutExerciseReplacement::updateOrCreate([
                'assignment_id' => $locked->id,
                'routine_day_exercise_id' => $slot->id,
                'performed_on' => today()->toDateString(),
            ], ['exercise_id' => $exerciseId]);
        });
    }

    private function isEditableSession(Assignment $assignment): bool
    {
        if ($assignment->status === 'active' && $assignment->end_date === null) {
            return true;
        }

        return $assignment->status === 'completed'
            && $assignment->start_date?->isToday() === true
            && $assignment->end_date?->isToday() === true;
    }
}
