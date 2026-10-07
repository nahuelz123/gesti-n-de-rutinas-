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

class WorkoutLoggerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gym = Gym::create(['name' => 'Test Gym']);

        $this->client = User::factory()->create([
            'role' => 'client',
            'gym_id' => $this->gym->id,
        ]);

        $this->routine = Routine::create([
            'gym_id' => $this->gym->id,
            'coach_id' => $this->client->id,
            'title' => 'Rutina Test',
        ]);

        $this->day = RoutineDay::create([
            'routine_id' => $this->routine->id,
            'day_number' => 1,
            'title' => 'Pecho',
        ]);

        $this->exercise = Exercise::create([
            'gym_id' => $this->gym->id,
            'title' => 'Press Banca',
            'muscle_group' => 'pecho',
        ]);

        $this->routineDayExercise = RoutineDayExercise::create([
            'routine_day_id' => $this->day->id,
            'exercise_id' => $this->exercise->id,
            'sets' => 3,
            'reps' => '10',
            'order' => 1,
        ]);

        $this->assignment = Assignment::create([
            'gym_id' => $this->gym->id,
            'client_id' => $this->client->id,
            'routine_id' => $this->routine->id,
            'status' => 'active',
            'assigned_at' => now(),
        ]);
    }

    public function test_client_can_see_routine_overview(): void
    {
        $this->actingAs($this->client);

        Livewire::test('client.workout-logger', ['assignment' => $this->assignment])
            ->assertSee('Tu entrenamiento')
            ->assertSee('Rutina Test')
            ->assertSee('Pecho');
    }

    public function test_client_can_select_day_and_start_training(): void
    {
        $this->actingAs($this->client);

        Livewire::test('client.workout-logger', ['assignment' => $this->assignment])
            ->call('selectDay', $this->day->id)
            ->assertSet('step', 'training')
            ->assertSet('currentExerciseIndex', 0)
            ->assertSee('Press Banca')
            ->assertSee('Serie 1');
    }

    public function test_client_cannot_select_day_from_another_routine(): void
    {
        $foreignRoutine = Routine::create([
            'gym_id' => $this->gym->id,
            'coach_id' => $this->client->id,
            'title' => 'Rutina ajena',
        ]);

        $foreignDay = RoutineDay::create([
            'routine_id' => $foreignRoutine->id,
            'day_number' => 1,
            'title' => 'Otro día',
        ]);

        $this->actingAs($this->client);

        Livewire::test('client.workout-logger', ['assignment' => $this->assignment])
            ->call('selectDay', $foreignDay->id)
            ->assertForbidden();
    }

    public function test_reps_are_entered_directly_and_prescription_range_is_kept_separate(): void
    {
        $this->routineDayExercise->update(['reps' => '6-8']);
        $this->actingAs($this->client);

        Livewire::test('client.workout-logger', ['assignment' => $this->assignment])
            ->call('selectDay', $this->day->id)
            ->assertSee('6-8 reps')
            ->assertSet('inputs.1.reps', '')
            ->assertSee('inputmode="numeric"', false)
            ->assertDontSee('wire:click.prevent="increaseReps', false);
    }

    public function test_client_can_log_valid_set(): void
    {
        $this->actingAs($this->client);

        Livewire::test('client.workout-logger', ['assignment' => $this->assignment])
            ->call('selectDay', $this->day->id)
            ->set('inputs.1.weight', 80)
            ->set('inputs.1.reps', 10)
            ->call('logSet', 1)
            ->assertDispatched('set-logged');

        $this->assertDatabaseHas('exercise_logs', [
            'assignment_id' => $this->assignment->id,
            'routine_day_exercise_id' => $this->routineDayExercise->id,
            'set_number' => 1,
            'weight' => 80,
            'reps' => 10,
        ]);
    }

    public function test_client_can_log_bodyweight_with_zero_load(): void
    {
        $this->actingAs($this->client);

        Livewire::test('client.workout-logger', ['assignment' => $this->assignment])
            ->call('selectDay', $this->day->id)
            ->set('inputs.1.weight', 0)
            ->set('inputs.1.reps', 12)
            ->call('logSet', 1)
            ->assertHasNoErrors()
            ->assertDispatched('set-logged');

        $this->assertDatabaseHas('exercise_logs', [
            'assignment_id' => $this->assignment->id,
            'routine_day_exercise_id' => $this->routineDayExercise->id,
            'set_number' => 1,
            'weight' => 0,
            'reps' => 12,
        ]);
    }

    public function test_client_can_log_bodyweight_with_empty_load(): void
    {
        $this->actingAs($this->client);

        Livewire::test('client.workout-logger', ['assignment' => $this->assignment])
            ->call('selectDay', $this->day->id)
            ->set('inputs.1.weight', '')
            ->set('inputs.1.reps', 15)
            ->call('logSet', 1)
            ->assertHasNoErrors()
            ->assertDispatched('set-logged');

        $this->assertDatabaseHas('exercise_logs', [
            'assignment_id' => $this->assignment->id,
            'routine_day_exercise_id' => $this->routineDayExercise->id,
            'set_number' => 1,
            'weight' => null,
            'reps' => 15,
        ]);
    }

    public function test_client_cannot_log_negative_weight(): void
    {
        $this->actingAs($this->client);

        Livewire::test('client.workout-logger', ['assignment' => $this->assignment])
            ->call('selectDay', $this->day->id)
            ->set('inputs.1.weight', -5)
            ->set('inputs.1.reps', 10)
            ->call('logSet', 1)
            ->assertHasErrors(['inputs.1.weight']);

        $this->assertDatabaseCount('exercise_logs', 0);
    }

    public function test_http_set_endpoints_support_bodyweight_and_reject_negative_weight(): void
    {
        $this->actingAs($this->client);

        $this->post(route('client.logs.store'), [
            'assignment_id' => $this->assignment->id,
            'routine_day_exercise_id' => $this->routineDayExercise->id,
            'set_number' => 1,
            'weight' => 0,
            'reps' => 10,
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('exercise_logs', [
            'assignment_id' => $this->assignment->id,
            'set_number' => 1,
            'weight' => 0,
        ]);

        $this->post(route('client.logs.store'), [
            'assignment_id' => $this->assignment->id,
            'routine_day_exercise_id' => $this->routineDayExercise->id,
            'set_number' => 2,
            'weight' => -2.5,
            'reps' => 10,
        ])->assertSessionHasErrors('weight');
    }

    public function test_last_performance_is_found_across_different_assignments(): void
    {
        $oldRoutine = Routine::create([
            'gym_id' => $this->gym->id,
            'coach_id' => $this->client->id,
            'title' => 'Rutina anterior',
        ]);

        $oldDay = RoutineDay::create([
            'routine_id' => $oldRoutine->id,
            'day_number' => 1,
            'title' => 'Torso',
        ]);

        $oldRde = RoutineDayExercise::create([
            'routine_day_id' => $oldDay->id,
            'exercise_id' => $this->exercise->id,
            'sets' => 3,
            'reps' => '8',
            'order' => 1,
        ]);

        $oldAssignment = Assignment::create([
            'gym_id' => $this->gym->id,
            'client_id' => $this->client->id,
            'routine_id' => $oldRoutine->id,
            'status' => 'completed',
            'assigned_at' => now()->subMonth(),
            'end_date' => yesterday(),
        ]);

        ExerciseLog::create([
            'assignment_id' => $oldAssignment->id,
            'routine_day_exercise_id' => $oldRde->id,
            'set_number' => 1,
            'weight' => 70,
            'reps' => 8,
            'logged_at' => yesterday()->setTime(18, 0),
        ]);

        $this->actingAs($this->client);

        Livewire::test('client.workout-logger', ['assignment' => $this->assignment])
            ->call('selectDay', $this->day->id)
            ->assertSee('Última marca de este ejercicio')
            ->assertSee('70 kg × 8 reps');
    }

    public function test_client_cannot_log_set_for_another_clients_assignment(): void
    {
        $otherClient = User::factory()->create(['role' => 'client', 'gym_id' => $this->gym->id]);
        $this->actingAs($otherClient);

        Livewire::test('client.workout-logger', ['assignment' => $this->assignment])
            ->assertForbidden();
    }

    public function test_client_cannot_log_invalid_set_number(): void
    {
        $this->actingAs($this->client);

        Livewire::test('client.workout-logger', ['assignment' => $this->assignment])
            ->call('selectDay', $this->day->id)
            ->call('logSet', 99)
            ->assertHasErrors('set_99');
    }

    public function test_tutorial_button_shows_when_media_exists(): void
    {
        $this->actingAs($this->client);
        $this->exercise->update(['gif_url' => 'https://example.com/test.gif']);

        Livewire::test('client.workout-logger', ['assignment' => $this->assignment])
            ->call('selectDay', $this->day->id)
            ->assertSee('Tutorial');
    }

    public function test_tutorial_button_hidden_when_no_media(): void
    {
        $this->actingAs($this->client);
        $this->exercise->update(['gif_url' => null, 'video_url' => null]);

        Livewire::test('client.workout-logger', ['assignment' => $this->assignment])
            ->call('selectDay', $this->day->id)
            ->assertDontSee('Tutorial');
    }

    public function test_rest_timer_ui_is_rendered_when_training(): void
    {
        $this->routineDayExercise->update(['rest' => 90]);
        $this->actingAs($this->client);

        Livewire::test('client.workout-logger', ['assignment' => $this->assignment])
            ->call('selectDay', $this->day->id)
            ->assertSee('90 s descanso')
            ->assertSee('vf-rest-timer', false)
            ->assertSee('OMITIR');
    }

    public function test_client_cannot_log_set_in_historical_routine(): void
    {
        $coach = User::factory()->create(['role' => 'coach', 'gym_id' => $this->gym->id]);

        $historical = Assignment::create([
            'gym_id' => $this->gym->id,
            'client_id' => $this->client->id,
            'assigned_by_id' => $coach->id,
            'routine_id' => $this->routine->id,
            'status' => 'completed',
            'end_date' => now()->subDay(),
        ]);

        $this->actingAs($this->client);

        Livewire::test('client.workout-logger', ['assignment' => $historical])
            ->call('selectDay', $this->day->id)
            ->set('inputs.1.weight', 80)
            ->set('inputs.1.reps', 10)
            ->call('logSet', 1)
            ->assertForbidden();
    }

    public function test_client_can_log_set_in_replay_session(): void
    {
        $coach = User::factory()->create(['role' => 'coach', 'gym_id' => $this->gym->id]);

        $replay = Assignment::create([
            'gym_id' => $this->gym->id,
            'client_id' => $this->client->id,
            'assigned_by_id' => $coach->id,
            'routine_id' => $this->routine->id,
            'status' => 'completed',
            'start_date' => today(),
            'end_date' => today(),
        ]);

        $this->actingAs($this->client);

        Livewire::test('client.workout-logger', ['assignment' => $replay])
            ->call('selectDay', $this->day->id)
            ->set('inputs.1.weight', 80)
            ->set('inputs.1.reps', 10)
            ->call('logSet', 1)
            ->assertDispatched('set-logged');

        $this->assertDatabaseHas('exercise_logs', [
            'assignment_id' => $replay->id,
            'weight' => 80,
            'reps' => 10,
        ]);
    }

    public function test_client_cannot_log_set_in_expired_replay(): void
    {
        $coach = User::factory()->create(['role' => 'coach', 'gym_id' => $this->gym->id]);

        $replay = Assignment::create([
            'gym_id' => $this->gym->id,
            'client_id' => $this->client->id,
            'assigned_by_id' => $coach->id,
            'routine_id' => $this->routine->id,
            'status' => 'completed',
            'start_date' => today()->subDay(),
            'end_date' => today()->subDay(),
        ]);

        $this->actingAs($this->client);

        Livewire::test('client.workout-logger', ['assignment' => $replay])
            ->call('selectDay', $this->day->id)
            ->set('inputs.1.weight', 80)
            ->set('inputs.1.reps', 10)
            ->call('logSet', 1)
            ->assertForbidden();
    }

    public function test_client_can_log_89_repetitions_when_entered_directly(): void
    {
        $this->actingAs($this->client);

        Livewire::test('client.workout-logger', ['assignment' => $this->assignment])
            ->call('selectDay', $this->day->id)
            ->set('inputs.1.weight', '30')
            ->set('inputs.1.reps', '89')
            ->call('logSet', 1)
            ->assertDispatched('set-logged');

        $this->assertDatabaseHas('exercise_logs', [
            'assignment_id' => $this->assignment->id,
            'routine_day_exercise_id' => $this->routineDayExercise->id,
            'set_number' => 1,
            'weight' => 30,
            'reps' => 89,
        ]);
    }

    public function test_client_cannot_log_more_than_100_repetitions(): void
    {
        $this->actingAs($this->client);

        Livewire::test('client.workout-logger', ['assignment' => $this->assignment])
            ->call('selectDay', $this->day->id)
            ->set('inputs.1.weight', 20)
            ->set('inputs.1.reps', 101)
            ->call('logSet', 1)
            ->assertHasErrors(['inputs.1.reps' => 'max']);

        $this->assertDatabaseMissing('exercise_logs', [
            'assignment_id' => $this->assignment->id,
            'routine_day_exercise_id' => $this->routineDayExercise->id,
            'set_number' => 1,
        ]);
    }

    public function test_client_routine_layout_loads_livewire_scripts_for_mobile_controls(): void
    {
        $this->actingAs($this->client);

        $this->get(route('client.routines.active'))
            ->assertOk()
            ->assertSee('livewire/livewire.js', false);
    }
}
