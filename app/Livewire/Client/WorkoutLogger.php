<?php

namespace App\Livewire\Client;

use App\Models\Assignment;
use App\Models\Exercise;
use App\Models\ExerciseLog;
use App\Models\WorkoutExerciseReplacement;
use App\Services\ExerciseSetRecorder;
use App\Support\RestDuration;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

class WorkoutLogger extends Component
{
    public Assignment $assignment;

    public $step = 'overview'; // overview, training, completed

    #[Url(as: 'day')]
    public $selectedDayId = null;

    #[Url(as: 'exercise')]
    public $selectedExerciseId = null;

    public $currentExerciseIndex = 0;

    public bool $showAlternatives = false;

    public string $alternativeSearch = '';

    #[Locked]
    public ?int $displayedExerciseId = null;

    #[Locked]
    public ?string $sessionDate = null;

    // [set_number => ['weight' => x, 'reps' => y]]
    public $inputs = [];

    public function mount(Assignment $assignment)
    {
        abort_unless(Auth::check() && $assignment->client_id === Auth::id(), 403);

        $this->assignment = $assignment;

        if ($this->selectedDayId !== null) {
            $this->selectDay($this->selectedDayId, $this->selectedExerciseId);
        }
    }

    public function selectDay($dayId, $exerciseId = null)
    {
        abort_unless($this->assignment->client_id === Auth::id(), 403);

        $day = $this->assignment->routine->days->firstWhere('id', (int) $dayId);
        abort_unless($day, 403);

        $this->selectedDayId = $day->id;
        unset($this->day, $this->exercises, $this->todayLogs);

        if ($exerciseId === null) {
            $logs = $this->todayLogs->groupBy('routine_day_exercise_id');
            $next = $this->exercises->first(fn ($exercise) => ($logs->get($exercise->id)?->unique('set_number')->count() ?? 0) < (int) $exercise->sets);
            $exerciseId = ($next ?? $this->exercises->first())?->id;
        }

        if ($exerciseId !== null) {
            $this->selectExercise($exerciseId);
        }
    }

    public function selectExercise($exerciseId): void
    {
        abort_unless($this->assignment->client_id === Auth::id(), 403);
        $index = $this->exercises->search(fn ($exercise) => $exercise->id === (int) $exerciseId);
        abort_if($index === false, 403);

        $this->showAlternatives = false;
        $this->alternativeSearch = '';
        $this->currentExerciseIndex = $index;
        $this->selectedExerciseId = $this->exercises[$index]->id;
        $this->step = 'training';
        unset($this->currentExercise, $this->performedExercise, $this->lastLog, $this->restSeconds, $this->restLabel);
        $this->resetValidation();
        $this->initInputsForCurrentExercise();
        $this->dispatch('exercise-changed', rest: $this->restSeconds);
    }

    public function updatedSelectedDayId(): void
    {
        if ($this->selectedDayId === null) {
            $this->exitTraining();

            return;
        }
        $this->selectDay($this->selectedDayId, $this->selectedExerciseId);
    }

    public function updatedSelectedExerciseId(): void
    {
        if ($this->selectedExerciseId !== null && $this->selectedDayId !== null) {
            $this->selectExercise($this->selectedExerciseId);
        }
    }

    public function getProgressUrlProperty(): string
    {
        return route('client.progress.exercise', [
            'exercise' => $this->performedExercise->id,
            'assignment' => $this->assignment->id,
            'day' => $this->selectedDayId,
            'routine_exercise' => $this->selectedExerciseId,
        ]);
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

    public function getPerformedExerciseProperty()
    {
        $slot = $this->currentExercise;
        if (! $slot) {
            return null;
        }

        $log = $this->todayLogs->firstWhere('routine_day_exercise_id', $slot->id);
        if ($log) {
            return $log->exercise ?? $slot->exercise;
        }

        return WorkoutExerciseReplacement::query()->with('exercise')
            ->where('assignment_id', $this->assignment->id)
            ->where('routine_day_exercise_id', $slot->id)
            ->whereDate('performed_on', today())->first()?->exercise ?? $slot->exercise;
    }

    public function getCanReplaceExerciseProperty(): bool
    {
        return $this->currentExercise && $this->todayLogs
            ->where('routine_day_exercise_id', $this->currentExercise->id)->isEmpty();
    }

    public function toggleAlternatives(): void
    {
        abort_unless($this->assignment->client_id === Auth::id(), 403);
        $this->showAlternatives = ! $this->showAlternatives;
    }

    public function getAlternativesProperty()
    {
        if (! $this->currentExercise || ! $this->showAlternatives) {
            return collect();
        }
        $query = Exercise::query()->visibleToGym(Auth::user()->gym_id)
            ->whereKeyNot($this->performedExercise->id);
        $search = mb_substr(trim($this->alternativeSearch), 0, 100);
        if ($search !== '') {
            $query->searchByName($search);
        }

        return $query->orderByRaw('CASE WHEN muscle_group = ? THEN 0 ELSE 1 END', [$this->currentExercise->exercise->muscle_group])
            ->orderBy('title')->limit(30)->get();
    }

    public function replaceExercise(int $exerciseId): void
    {
        abort_unless($this->currentExercise, 403);
        app(ExerciseSetRecorder::class)->replace($this->assignment, Auth::user(), $this->currentExercise->id, $exerciseId);
        unset($this->performedExercise, $this->lastLog, $this->todayLogs);
        $this->showAlternatives = false;
        $this->alternativeSearch = '';
        $this->resetValidation();
        $this->initInputsForCurrentExercise();
    }

    public function getTodayLogsProperty()
    {
        if (! $this->selectedDayId) {
            return collect();
        }

        $exerciseIds = $this->exercises->pluck('id');

        return ExerciseLog::query()
            ->where('assignment_id', $this->assignment->id)
            ->whereIn('routine_day_exercise_id', $exerciseIds)
            ->whereDate('logged_at', today())
            ->get();
    }

    /**
     * Last performance of the same catalog exercise, even if it belonged to
     * another routine/assignment. This keeps progression references useful
     * after a coach changes the client's program.
     */
    public function getLastLogProperty()
    {
        $current = $this->performedExercise;

        if (! $current) {
            return null;
        }

        return ExerciseLog::query()
            ->whereHas('assignment', fn ($query) => $query->where('client_id', Auth::id()))
            ->forPerformedExercise($current->id)
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

        $this->displayedExerciseId = $this->performedExercise?->id;
        $this->sessionDate = today()->toDateString();

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
        return $this->normalizeRestSeconds($this->currentExercise?->rest) ?: 90;
    }

    public function getRestLabelProperty(): ?string
    {
        $seconds = $this->restSeconds;

        if ($seconds <= 0) {
            return null;
        }

        if ($seconds % 60 === 0) {
            $minutes = intdiv($seconds, 60);

            return $minutes.' '.($minutes === 1 ? 'min' : 'min').' descanso';
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
        $this->resetValidation('workout_context');

        if ($setNumber < 1 || $setNumber > 20) {
            $this->addError('set_'.$setNumber, 'Serie inválida.');

            return;
        }

        $currentExercise = $this->currentExercise;
        abort_unless($currentExercise, 404);

        if ($setNumber > (int) $currentExercise->sets) {
            $this->addError('set_'.$setNumber, 'Esta serie no está configurada para el ejercicio.');

            return;
        }

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

        try {
            app(ExerciseSetRecorder::class)->record(
                $this->assignment,
                $user,
                (int) $currentExercise->id,
                (int) $setNumber,
                $weight,
                $reps,
                $this->displayedExerciseId ?? -1,
                $this->sessionDate ?? '',
            );
        } catch (ValidationException $exception) {
            if (! isset($exception->errors()['workout_context'])) {
                throw $exception;
            }
            // Discard entered values before adopting the new exercise/date context.
            unset($this->todayLogs, $this->performedExercise, $this->lastLog);
            $this->initInputsForCurrentExercise();
            $this->addError('workout_context', $exception->errors()['workout_context'][0]);

            return;
        }

        unset($this->todayLogs, $this->performedExercise);

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
            $this->selectExercise($this->exercises[$this->currentExerciseIndex + 1]->id);
        } else {
            $this->step = 'completed';
        }
    }

    public function prevExercise()
    {
        if ($this->currentExerciseIndex > 0) {
            $this->selectExercise($this->exercises[$this->currentExerciseIndex - 1]->id);
        }
    }

    public function exitTraining()
    {
        $this->step = 'overview';
        $this->selectedDayId = null;
        $this->selectedExerciseId = null;
        $this->currentExerciseIndex = 0;
        $this->resetValidation();
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
        return RestDuration::seconds($rest) ?? 0;
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
        $this->assignment->loadMissing('routine.days.exercises.exercise');

        return view('livewire.client.workout-logger');
    }
}
