<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Exercise;
use App\Models\ExerciseLog;
use App\Models\User;
use App\Models\WorkoutExerciseReplacement;
use App\Services\TrainingExperience;
use App\Support\ExperienceDemo;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExperienceController extends Controller
{
    public function demo(): View
    {
        $this->ensureEnabled();

        return view('client.dashboard', ExperienceDemo::dashboard());
    }

    public function demoCharacter(): View
    {
        $this->ensureEnabled();

        return view('experience.character', ExperienceDemo::context());
    }

    public function character(Request $request): View
    {
        $this->ensureEnabled();

        return view('experience.character', $this->context($request->user()));
    }

    private function ensureEnabled(): void
    {
        abort_unless(config('experience.enabled'), 404);
    }

    private function context(User $client): array
    {
        $active = Assignment::query()->where('client_id', $client->id)
            ->where('status', 'active')->whereNull('end_date')
            ->with('routine.days.exercises.exercise')->latest('assigned_at')->first();
        $days = $active?->routine?->days->sortBy('day_number')->values() ?? collect();
        $day = $days->first();
        if ($active && $days->isNotEmpty()) {
            $last = ExerciseLog::query()->where('assignment_id', $active->id)
                ->with('routineDayExercise.routineDay')->latest('logged_at')->first();
            $index = $days->search(fn ($item) => $item->id === $last?->routineDayExercise?->routineDay?->id);
            if ($index !== false) {
                $day = $last->logged_at->isToday() ? $days[$index] : $days[($index + 1) % $days->count()];
            }
        }
        $slot = $day?->exercises->sortBy('order')->first();
        $workoutCompleted = false;
        if ($active && $day) {
            $todayLogs = ExerciseLog::query()->where('assignment_id', $active->id)
                ->whereIn('routine_day_exercise_id', $day->exercises->pluck('id'))->whereDate('logged_at', today())->get();
            $pending = $day->exercises->sortBy('order')->first(fn ($item) => $todayLogs
                ->where('routine_day_exercise_id', $item->id)->unique('set_number')->count() < (int) $item->sets);
            $workoutCompleted = $day->exercises->isNotEmpty() && $pending === null;
            $slot = $pending ?? $slot;
        }
        $exercise = $slot?->exercise;
        if ($active && $slot) {
            $replacementId = WorkoutExerciseReplacement::query()->where('assignment_id', $active->id)
                ->where('routine_day_exercise_id', $slot->id)->whereDate('performed_on', today())->value('exercise_id');
            if ($replacementId) {
                $exercise = Exercise::withTrashed()->find($replacementId) ?? $exercise;
            }
        }

        $experience = app(TrainingExperience::class)->forClient($client);

        return [
            'isDemo' => false,
            'displayName' => $client->name,
            'avatarStorageKey' => 'visionfit:avatar:'.$client->id,
            'trainingUrl' => route('client.routines.active', array_filter(['day' => $day?->id, 'exercise' => $slot?->id])),
            'progressUrl' => route('client.progress.index'),
            'characterUrl' => route('client.character'),
            'nutritionUrl' => route('client.nutrition.index'),
            'workoutTitle' => $day?->title ?? 'Tu próximo entrenamiento',
            'workoutSubtitle' => $active?->routine?->title ?? 'Tu entrenador puede asignarte una rutina para empezar.',
            'workoutCompleted' => $workoutCompleted,
            'hasAssignedRoutine' => $active !== null,
            'nextExercise' => ! $workoutCompleted && $exercise && $slot ? [
                'title' => $exercise->title, 'sets' => $slot->sets, 'reps' => $slot->reps, 'rest' => $slot->rest,
            ] : null,
            'initialMuscleMap' => $experience['today_map'],
            'demoExercises' => [],
            'experience' => $experience,
            'weekActivity' => $this->weekActivity($client),
        ];
    }

    private function weekActivity(User $client): array
    {
        $dates = ExerciseLog::query()->whereHas('assignment', fn ($query) => $query->withTrashed()->where('client_id', $client->id))
            ->whereBetween('logged_at', [now()->startOfWeek(), now()])->where('reps', '>', 0)
            ->where(fn ($query) => $query->whereNull('weight')->orWhere('weight', '>=', 0))
            ->selectRaw('DATE(logged_at) as training_date')->distinct()->pluck('training_date');
        $labels = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];

        return collect($labels)->map(function ($label, $index) use ($dates): array {
            $date = now()->startOfWeek()->addDays($index);

            return ['label' => $label, 'active' => $dates->contains($date->toDateString()), 'today' => $date->isToday()];
        })->all();
    }
}
