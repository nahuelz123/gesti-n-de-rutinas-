<?php

namespace App\Livewire\Client;

use App\Models\Assignment;
use App\Models\ExerciseLog;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class WorkoutLogger extends Component
{
    public Assignment $assignment;
    public $step = 'overview'; // overview, training, completed
    public $selectedDayId = null;
    public $currentExerciseIndex = 0;

    // [set_number => ['weight' => x, 'reps' => y]]
    public $inputs = [];

    public function mount(Assignment $assignment)
    {
        abort_unless(Auth::check() && $assignment->client_id === Auth::id(), 403);

        $this->assignment = $assignment;
    }

    public function selectDay($dayId)
    {
        abort_unless($this->assignment->client_id === Auth::id(), 403);

        $day = $this->assignment->routine->days->firstWhere('id', (int) $dayId);
        abort_unless($day, 403);

        $this->selectedDayId = $day->id;
        $this->currentExerciseIndex = $this->firstIncompleteExerciseIndex();
        $this->step = 'training';
        $this->initInputsForCurrentExercise();
    }

    public function getDayProperty()
    {
        if (! $this->selectedDayId) {
            return null;
        }

        return $this->assignment->routine->days->firstWhere('id', (int) $this->selectedDayId);
    }

    public function getExercisesProperty()
    {
        if (! $this->day) {
            return collect();
        }

        return $this->day->exercises->sortBy('order')->values();
    }

    public function getCurrentExerciseProperty()
    {
        return $this->exercises[$this->currentExerciseIndex] ?? null;
    }

    public function getTodayAssignmentLogsProperty()
    {
        return ExerciseLog::query()
            ->where('assignment_id', $this->assignment->id)
            ->whereDate('logged_at', today())
            ->get();
    }

    public function getTodayLogsProperty()
    {
        if (! $this->selectedDayId) {
            return collect();
        }

        $exerciseIds = $this->exercises->pluck('id');

        return $this->todayAssignmentLogs
            ->whereIn('routine_day_exercise_id', $exerciseIds)
            ->values();
    }

    public function getDayTotalSetsProperty(): int
    {
        return (int) $this->exercises->sum(fn ($exercise) => (int) $exercise->sets);
    }

    public function getDayCompletedSetsProperty(): int
    {
        return $this->todayLogs
            ->unique(fn ($log) => $log->routine_day_exercise_id . ':' . $log->set_number)
            ->count();
    }

    public function getDayProgressPercentProperty(): int
    {
        if ($this->dayTotalSets <= 0) {
            return 0;
        }

        return min(100, (int) round(($this->dayCompletedSets / $this->dayTotalSets) * 100));
    }

    public function getCurrentExerciseCompletedSetsProperty(): int
    {
        if (! $this->currentExercise) {
            return 0;
        }

        return $this->todayLogs
            ->where('routine_day_exercise_id', $this->currentExercise->id)
            ->unique('set_number')
            ->count();
    }

    public function dayProgress($dayId): array
    {
        $day = $this->assignment->routine->days->firstWhere('id', (int) $dayId);

        if (! $day) {
            return ['completed' => 0, 'total' => 0, 'percent' => 0];
        }

        $exerciseIds = $day->exercises->pluck('id');
        $total = (int) $day->exercises->sum(fn ($exercise) => (int) $exercise->sets);
        $completed = $this->todayAssignmentLogs
            ->whereIn('routine_day_exercise_id', $exerciseIds)
            ->unique(fn ($log) => $log->routine_day_exercise_id . ':' . $log->set_number)
            ->count();

        return [
            'completed' => $completed,
            'total' => $total,
            'percent' => $total > 0 ? min(100, (int) round(($completed / $total) * 100)) : 0,
        ];
    }

    /**
     * Last performance of the same catalog exercise, even if it belonged to
     * another routine/assignment. This keeps progression references useful
     * after a coach changes the client's program.
     */
    public function getLastLogProperty()
    {
        $current = $this->currentExercise;

        if (! $current || ! $current->exercise_id) {
            return null;
        }

        return ExerciseLog::query()
            ->whereHas('assignment', fn ($query) => $query->where('client_id', Auth::id()))
            ->whereHas('routineDayExercise', fn ($query) => $query->where('exercise_id', $current->exercise_id))
            ->whereDate('logged_at', '<', today())
            ->latest('logged_at')
            ->first();
    }

    public function initInputsForCurrentExercise()
    {
        $this->inputs = [];
        $current = $this->currentExercise;

        if (! $current) {
            return;
        }

        $logs = $this->todayLogs
            ->where('routine_day_exercise_id', $current->id)
            ->keyBy('set_number');

        for ($i = 1; $i <= $current->sets; $i++) {
            if ($logs->has($i)) {
                $this->inputs[$i] = [
                    'weight' => $this->formatWeightForInput($logs[$i]->weight),
                    'reps' => $logs[$i]->reps,
                ];
            } else {
                $this->inputs[$i] = [
                    'weight' => '',
                    'reps' => '',
                ];
            }
        }
    }

    public function useLastLog($setNumber)
    {
        $last = $this->lastLog;

        if (! $last || ! isset($this->inputs[$setNumber])) {
            return;
        }

        // Keep bodyweight visually empty so it does not look like a loaded zero.
        $this->inputs[$setNumber]['weight'] = $last->weight === null || (float) $last->weight <= 0
            ? ''
            : $this->formatWeightForInput($last->weight);
        $this->inputs[$setNumber]['reps'] = $last->reps;
    }

    public function copyPreviousSet($setNumber)
    {
        $setNumber = (int) $setNumber;

        if ($setNumber <= 1 || ! isset($this->inputs[$setNumber], $this->inputs[$setNumber - 1])) {
            return;
        }

        $previous = $this->inputs[$setNumber - 1];

        if (($previous['weight'] ?? '') === '' && ($previous['reps'] ?? '') === '') {
            return;
        }

        $this->inputs[$setNumber] = [
            'weight' => $previous['weight'] ?? '',
            'reps' => $previous['reps'] ?? '',
        ];
    }

    public function getRestSecondsProperty(): int
    {
        return $this->normalizeRestSeconds($this->currentExercise?->rest);
    }

    public function getRestLabelProperty(): ?string
    {
        $seconds = $this->restSeconds;

        if ($seconds <= 0) {
            return null;
        }

        if ($seconds % 60 === 0) {
            $minutes = intdiv($seconds, 60);

            return $minutes . ' ' . ($minutes === 1 ? 'min' : 'min') . ' descanso';
        }

        if ($seconds > 60) {
            $minutes = intdiv($seconds, 60);
            $remaining = $seconds % 60;

            return "{$minutes} min {$remaining} s descanso";
        }

        return "{$seconds} s descanso";
    }

    public function logSet($setNumber)
    {
        $user = Auth::user();

        if ($setNumber < 1 || $setNumber > 20) {
            $this->addError('set_' . $setNumber, 'Serie inválida.');
            return;
        }

        $currentExercise = $this->currentExercise;
        abort_unless($currentExercise, 404);

        $rawWeight = $this->inputs[$setNumber]['weight'] ?? null;

        if (is_string($rawWeight)) {
            $rawWeight = trim($rawWeight);

            if ($rawWeight !== '') {
                $rawWeight = str_replace(',', '.', $rawWeight);
            }
        }

        $this->inputs[$setNumber]['weight'] = $rawWeight === '' ? null : $rawWeight;

        $validated = $this->validate([
            "inputs.{$setNumber}.weight" => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            "inputs.{$setNumber}.reps" => ['required', 'integer', 'min:1', 'max:100'],
        ], [
            "inputs.{$setNumber}.weight.numeric" => 'Ingresá un peso válido.',
            "inputs.{$setNumber}.weight.min" => 'El peso no puede ser negativo.',
            "inputs.{$setNumber}.weight.max" => 'El peso supera el máximo admitido.',
            "inputs.{$setNumber}.reps.required" => 'Ingresá las repeticiones realizadas.',
            "inputs.{$setNumber}.reps.integer" => 'Las repeticiones deben ser un número entero.',
            "inputs.{$setNumber}.reps.min" => 'Completá al menos 1 repetición.',
            "inputs.{$setNumber}.reps.max" => 'El máximo admitido es 100 repeticiones.',
        ]);

        $validatedWeight = $validated['inputs'][$setNumber]['weight'] ?? null;
        $weight = $validatedWeight === null || $validatedWeight === ''
            ? null
            : (float) $validatedWeight;
        $reps = (int) $validated['inputs'][$setNumber]['reps'];

        // Solo el dueño del assignment puede registrar series.
        if (! $user || $this->assignment->client_id !== $user->id) {
            abort(403);
        }

        $isActive = $this->assignment->status === 'active' && $this->assignment->end_date === null;
        $isReplay = $this->assignment->status === 'completed'
            && $this->assignment->start_date !== null
            && \Carbon\Carbon::parse($this->assignment->start_date)->isToday()
            && $this->assignment->end_date !== null
            && \Carbon\Carbon::parse($this->assignment->end_date)->isToday();

        if (! $isActive && ! $isReplay) {
            abort(403);
        }

        // Ensure the current routine-day exercise belongs to this assignment's routine.
        $belongs = $this->assignment->routine
            ->days()
            ->whereHas('exercises', fn ($query) => $query->where('routine_day_exercises.id', $currentExercise->id))
            ->exists();

        if (! $belongs) {
            abort(403);
        }

        $existing = ExerciseLog::query()
            ->where('assignment_id', $this->assignment->id)
            ->where('routine_day_exercise_id', $currentExercise->id)
            ->where('set_number', $setNumber)
            ->whereDate('logged_at', today())
            ->first();

        $payload = [
            'weight' => $weight,
            'reps' => $reps,
            'logged_at' => now(),
        ];

        if ($existing) {
            $existing->update($payload);
        } else {
            ExerciseLog::create($payload + [
                'assignment_id' => $this->assignment->id,
                'routine_day_exercise_id' => $currentExercise->id,
                'set_number' => $setNumber,
            ]);
        }

        $this->dispatch(
            'set-logged',
            set: $setNumber,
            nextSet: $setNumber < (int) $currentExercise->sets ? $setNumber + 1 : null,
            rest: $this->restSeconds,
        );
    }

    public function nextExercise()
    {
        if ($this->currentExerciseIndex < $this->exercises->count() - 1) {
            $this->currentExerciseIndex++;
            $this->initInputsForCurrentExercise();
            $this->dispatch('exercise-changed');
        } else {
            $this->step = 'completed';
        }
    }

    public function prevExercise()
    {
        if ($this->currentExerciseIndex > 0) {
            $this->currentExerciseIndex--;
            $this->initInputsForCurrentExercise();
            $this->dispatch('exercise-changed');
        }
    }

    public function exitTraining()
    {
        $this->step = 'overview';
        $this->selectedDayId = null;
        $this->currentExerciseIndex = 0;
    }

    private function firstIncompleteExerciseIndex(): int
    {
        $exercises = $this->exercises;

        if ($exercises->isEmpty()) {
            return 0;
        }

        foreach ($exercises as $index => $exercise) {
            $completed = $this->todayLogs
                ->where('routine_day_exercise_id', $exercise->id)
                ->unique('set_number')
                ->count();

            if ($completed < (int) $exercise->sets) {
                return (int) $index;
            }
        }

        return max(0, $exercises->count() - 1);
    }

    public function isSetCompleted($setNumber)
    {
        if (! $this->currentExercise) {
            return false;
        }

        return $this->todayLogs
            ->where('routine_day_exercise_id', $this->currentExercise->id)
            ->where('set_number', $setNumber)
            ->isNotEmpty();
    }

    private function normalizeRestSeconds($rest): int
    {
        if ($rest === null || $rest === '') {
            return 0;
        }

        if (is_numeric($rest)) {
            return max(0, (int) round((float) $rest));
        }

        $value = strtolower(trim((string) $rest));

        if (preg_match('/^(\d+(?:[.,]\d+)?)\s*(min|mins|minuto|minutos)$/u', $value, $matches)) {
            return max(0, (int) round((float) str_replace(',', '.', $matches[1]) * 60));
        }

        if (preg_match('/^(\d+)\s*(s|seg|segs|segundo|segundos)$/u', $value, $matches)) {
            return max(0, (int) $matches[1]);
        }

        if (preg_match('/^(\d+)\s*:\s*(\d{1,2})$/', $value, $matches)) {
            return max(0, ((int) $matches[1] * 60) + (int) $matches[2]);
        }

        if (preg_match('/(\d+)/', $value, $matches)) {
            return max(0, (int) $matches[1]);
        }

        return 0;
    }

    private function formatWeightForInput($weight): string
    {
        if ($weight === null) {
            return '';
        }

        return rtrim(rtrim(number_format((float) $weight, 2, '.', ''), '0'), '.');
    }

    public function render()
    {
        return view('livewire.client.workout-logger');
    }
}
