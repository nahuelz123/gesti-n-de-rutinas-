<?php

namespace App\Livewire\Client;

use Livewire\Component;
use App\Models\Assignment;
use App\Models\RoutineDay;
use App\Models\ExerciseLog;
use Illuminate\Support\Facades\Auth;

class WorkoutLogger extends Component
{
    public Assignment $assignment;
    public $step = 'overview'; // 'overview', 'training', 'completed'
    public $selectedDayId = null;
    public $currentExerciseIndex = 0;

    // We store inputs for the currently active exercise sets
    public $inputs = []; // [set_number => ['weight' => x, 'reps' => y]]

    public function mount(Assignment $assignment)
    {
        $this->assignment = $assignment;
    }

    public function selectDay($dayId)
    {
        $this->selectedDayId = $dayId;
        $this->currentExerciseIndex = 0;
        $this->step = 'training';
        $this->initInputsForCurrentExercise();
    }

    public function getDayProperty()
    {
        if (!$this->selectedDayId) return null;
        return $this->assignment->routine->days->firstWhere('id', $this->selectedDayId);
    }

    public function getExercisesProperty()
    {
        if (!$this->day) return collect();
        return $this->day->exercises->sortBy('order')->values();
    }

    public function getCurrentExerciseProperty()
    {
        return $this->exercises[$this->currentExerciseIndex] ?? null;
    }

    public function getTodayLogsProperty()
    {
        if (!$this->selectedDayId) return collect();
        
        $exerciseIds = $this->exercises->pluck('id');
        return ExerciseLog::where('assignment_id', $this->assignment->id)
            ->whereIn('routine_day_exercise_id', $exerciseIds)
            ->whereDate('logged_at', today())
            ->get();
    }

    public function getLastLogProperty()
    {
        $current = $this->currentExercise;
        if (!$current) return null;

        return ExerciseLog::where('assignment_id', $this->assignment->id)
            ->where('routine_day_exercise_id', $current->id)
            ->whereDate('logged_at', '<', today())
            ->orderBy('logged_at', 'desc')
            ->first();
    }

    public function initInputsForCurrentExercise()
    {
        $this->inputs = [];
        $current = $this->currentExercise;
        if (!$current) return;

        // Initialize inputs for all sets
        $logs = $this->todayLogs->where('routine_day_exercise_id', $current->id)->keyBy('set_number');
        
        for ($i = 1; $i <= $current->sets; $i++) {
            if ($logs->has($i)) {
                $this->inputs[$i] = [
                    'weight' => rtrim(rtrim(number_format($logs[$i]->weight, 2, '.', ''), '0'), '.'),
                    'reps' => $logs[$i]->reps,
                ];
            } else {
                $this->inputs[$i] = [
                    'weight' => '',
                    'reps' => $current->reps ?? '',
                ];
            }
        }
    }

    public function useLastLog($setNumber)
    {
        $last = $this->lastLog;
        if ($last) {
            $this->inputs[$setNumber]['weight'] = rtrim(rtrim(number_format($last->weight, 2, '.', ''), '0'), '.');
            $this->inputs[$setNumber]['reps'] = $last->reps;
        }
    }

    public function increaseWeight($setNumber)
    {
        $val = (float)($this->inputs[$setNumber]['weight'] ?? 0);
        $this->inputs[$setNumber]['weight'] = $val + 2.5;
    }

    public function decreaseWeight($setNumber)
    {
        $val = (float)($this->inputs[$setNumber]['weight'] ?? 0);
        $this->inputs[$setNumber]['weight'] = max(0, $val - 2.5);
    }

    public function increaseReps($setNumber)
    {
        $val = (int)($this->inputs[$setNumber]['reps'] ?? 0);
        $this->inputs[$setNumber]['reps'] = $val + 1;
    }

    public function decreaseReps($setNumber)
    {
        $val = (int)($this->inputs[$setNumber]['reps'] ?? 0);
        $this->inputs[$setNumber]['reps'] = max(0, $val - 1);
    }

    public function logSet($setNumber)
    {
        $user = Auth::user();
        
        if ($setNumber < 1 || $setNumber > 20) {
            $this->addError('set_' . $setNumber, 'Serie inválida.');
            return;
        }

        $weight = isset($this->inputs[$setNumber]['weight']) && $this->inputs[$setNumber]['weight'] !== '' ? (float)$this->inputs[$setNumber]['weight'] : null;
        $reps = isset($this->inputs[$setNumber]['reps']) && $this->inputs[$setNumber]['reps'] !== '' ? (int)$this->inputs[$setNumber]['reps'] : null;

        // 1) Solo dueÃ±o del assignment
        if ($this->assignment->client_id !== $user->id) {
            abort(403);
        }

        // 2) Solo rutina activa
        if ($this->assignment->status !== 'active' || $this->assignment->end_date !== null) {
            abort(403);
        }

        $currentExercise = $this->currentExercise;

        // 3) Asegurar que ese routine_day_exercise pertenece a la rutina del assignment
        $belongs = $this->assignment->routine
            ->days()
            ->whereHas('exercises', fn ($q) => $q->where('routine_day_exercises.id', $currentExercise->id))
            ->exists();

        if (!$belongs) {
            abort(403);
        }

        // Guardar/Actualizar
        $existing = ExerciseLog::query()
            ->where('assignment_id', $this->assignment->id)
            ->where('routine_day_exercise_id', $currentExercise->id)
            ->where('set_number', $setNumber)
            ->whereDate('logged_at', today())
            ->first();

        if ($existing) {
            $existing->update([
                'weight' => $weight,
                'reps' => $reps,
                'logged_at' => now(),
            ]);
        } else {
            ExerciseLog::create([
                'assignment_id' => $this->assignment->id,
                'routine_day_exercise_id' => $currentExercise->id,
                'set_number' => $setNumber,
                'weight' => $weight,
                'reps' => $reps,
                'logged_at' => now(),
            ]);
        }
        
        // Trigger client side success animation
        $this->dispatch('set-logged', ['set' => $setNumber]);
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
    }

    public function isSetCompleted($setNumber)
    {
        return $this->todayLogs->where('routine_day_exercise_id', $this->currentExercise->id)
                               ->where('set_number', $setNumber)
                               ->isNotEmpty();
    }

    public function render()
    {
        return view('livewire.client.workout-logger');
    }
}

