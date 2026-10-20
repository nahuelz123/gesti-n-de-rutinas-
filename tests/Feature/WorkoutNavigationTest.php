<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Exercise;
use App\Models\ExerciseLog;
use App\Models\Gym;
use App\Models\Routine;
use App\Models\RoutineDay;
use App\Models\RoutineDayExercise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WorkoutNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_day_resumes_at_the_first_unfinished_exercise(): void
    {
        [$client, $assignment, $day, $rows] = $this->workout();
        ExerciseLog::create(['assignment_id' => $assignment->id, 'routine_day_exercise_id' => $rows[0]->id, 'set_number' => 1, 'weight' => 60, 'reps' => 10, 'logged_at' => now()]);
        $this->actingAs($client);
        Livewire::test('client.workout-logger', ['assignment' => $assignment])
            ->call('selectDay', $day->id)
            ->assertSet('selectedExerciseId', $rows[1]->id)
            ->assertSet('currentExerciseIndex', 1);
    }

    public function test_client_can_choose_any_exercise_in_the_selected_day(): void
    {
        [$client, $assignment, $day, $rows] = $this->workout();
        $this->actingAs($client);
        Livewire::test('client.workout-logger', ['assignment' => $assignment])
            ->call('selectDay', $day->id)
            ->assertSee('Elegir ejercicio')
            ->call('selectExercise', $rows[2]->id)
            ->assertSet('currentExerciseIndex', 2)
            ->assertSet('selectedExerciseId', $rows[2]->id)
            ->call('prevExercise')
            ->assertSet('selectedExerciseId', $rows[1]->id);
    }

    public function test_return_from_progress_restores_the_exact_day_and_exercise(): void
    {
        [$client, $assignment, $day, $rows] = $this->workout();
        $this->actingAs($client);
        Livewire::withQueryParams(['day' => $day->id, 'exercise' => $rows[2]->id])
            ->test('client.workout-logger', ['assignment' => $assignment])
            ->assertSet('step', 'training')
            ->assertSet('currentExerciseIndex', 2)
            ->assertSee('Ejercicio 3');

        $returnUrl = route('client.routines.active', ['day' => $day->id, 'exercise' => $rows[2]->id]);
        $this->get(route('client.progress.exercise', [
            'exercise' => $rows[2]->exercise_id,
            'assignment' => $assignment->id,
            'day' => $day->id,
            'routine_exercise' => $rows[2]->id,
        ]))->assertOk()->assertSee('Volver al entrenamiento')->assertSee($returnUrl);
    }

    public function test_foreign_navigation_context_does_not_expose_another_assignment(): void
    {
        [$client, $assignment, $day, $rows] = $this->workout();
        $otherClient = User::factory()->create(['role' => 'client', 'gym_id' => $client->gym_id]);
        $this->actingAs($otherClient)->get(route('client.progress.exercise', [
            'exercise' => $rows[0]->exercise_id,
            'assignment' => $assignment->id,
            'day' => $day->id,
            'routine_exercise' => $rows[0]->id,
        ]))->assertNotFound();
    }

    public function test_unknown_exercise_cannot_be_selected_even_with_a_tampered_id(): void
    {
        [$client, $assignment, $day] = $this->workout();
        $this->actingAs($client);
        Livewire::test('client.workout-logger', ['assignment' => $assignment])
            ->call('selectDay', $day->id)
            ->call('selectExercise', 999999)
            ->assertForbidden();
    }

    public function test_a_missing_rest_still_starts_a_ninety_second_timer(): void
    {
        [$client, $assignment, $day] = $this->workout();
        $this->actingAs($client);
        Livewire::test('client.workout-logger', ['assignment' => $assignment])
            ->call('selectDay', $day->id)
            ->set('inputs.1.reps', 10)->call('logSet', 1)
            ->assertDispatched('set-logged', fn ($event, $params) => $params['rest'] === 90);
    }

    public function test_rest_ranges_and_combined_units_are_read_in_seconds(): void
    {
        [$client, $assignment, $day, $rows] = $this->workout();
        $rows[0]->update(['rest' => '2-3 min']);
        $rows[1]->update(['rest' => '1 min 30 s']);
        $this->actingAs($client);
        Livewire::test('client.workout-logger', ['assignment' => $assignment])
            ->call('selectDay', $day->id)
            ->assertSet('restSeconds', 120)
            ->call('selectExercise', $rows[1]->id)
            ->assertSet('restSeconds', 90);
    }

    private function workout(): array
    {
        $gym = Gym::create(['name' => 'Gym navegación']);
        $client = User::factory()->create(['role' => 'client', 'gym_id' => $gym->id]);
        $routine = Routine::create(['gym_id' => $gym->id, 'coach_id' => $client->id, 'title' => 'Rutina']);
        $day = RoutineDay::create(['routine_id' => $routine->id, 'title' => 'Torso', 'day_number' => 1]);
        $rows = collect(range(1, 3))->map(function ($i) use ($day) {
            $exercise = Exercise::create(['title' => 'Ejercicio '.$i, 'muscle_group' => 'pecho', 'is_global' => true]);

            return RoutineDayExercise::create(['routine_day_id' => $day->id, 'exercise_id' => $exercise->id, 'sets' => 1, 'reps' => '8-10', 'order' => $i]);
        });
        $assignment = Assignment::create(['gym_id' => $gym->id, 'client_id' => $client->id, 'routine_id' => $routine->id, 'status' => 'active', 'assigned_at' => now()]);

        return [$client, $assignment, $day, $rows];
    }
}
