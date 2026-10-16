<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\ExerciseLog;
use App\Models\RoutineDayExercise;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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
        ?int $reps
    ): bool {
        return DB::transaction(function () use ($assignment, $client, $routineDayExerciseId, $setNumber, $weight, $reps): bool {
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

            $values = ['weight' => $weight, 'reps' => $reps, 'logged_at' => now()];

            if ($existing !== null) {
                $existing->update($values);

                return true;
            }

            ExerciseLog::create($values + [
                'assignment_id' => $lockedAssignment->id,
                'routine_day_exercise_id' => $exercise->id,
                'set_number' => $setNumber,
            ]);

            return false;
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
