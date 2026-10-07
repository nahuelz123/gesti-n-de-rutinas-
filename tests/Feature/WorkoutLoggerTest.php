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

    public function test_client_can_see_routine_overview()
    {
        $this->actingAs($this->client);
        
        Livewire::test('client.workout-logger', ['assignment' => $this->assignment])
            ->assertSee('TU RUTINA')
            ->assertSee('Rutina Test')
            ->assertSee('Pecho');
    }

    public function test_client_can_select_day_and_start_training()
    {
        $this->actingAs($this->client);
        
        Livewire::test('client.workout-logger', ['assignment' => $this->assignment])
            ->call('selectDay', $this->day->id)
            ->assertSet('step', 'training')
            ->assertSet('currentExerciseIndex', 0)
            ->assertSee('Press Banca')
            ->assertSee('Serie 1');
    }

    public function test_reps_are_entered_directly_and_prescription_range_is_kept_separate()
    {
        $this->routineDayExercise->update(['reps' => '6-8']);
        $this->actingAs($this->client);

        Livewire::test('client.workout-logger', ['assignment' => $this->assignment])
            ->call('selectDay', $this->day->id)
            ->assertSee('6-8 reps')
            ->assertSet('inputs.1.reps', '')
            ->assertSee('inputmode="numeric"', false)
            ->assertSee('placeholder="Reps"', false)
            ->assertDontSee('wire:click.prevent="increaseReps', false);
    }

    public function test_client_can_log_valid_set()
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

    public function test_client_cannot_log_zero_or_negative_weight(): void
    {
        $this->actingAs($this->client);

        foreach ([-5, 0] as $weight) {
            Livewire::test('client.workout-logger', ['assignment' => $this->assignment])
                ->call('selectDay', $this->day->id)
                ->set('inputs.1.weight', $weight)
                ->set('inputs.1.reps', 10)
                ->call('logSet', 1)
                ->assertHasErrors(['inputs.1.weight']);
        }

        $this->assertDatabaseCount('exercise_logs', 0);
    }

    public function test_client_cannot_log_set_without_weight(): void
    {
        $this->actingAs($this->client);

        Livewire::test('client.workout-logger', ['assignment' => $this->assignment])
            ->call('selectDay', $this->day->id)
            ->set('inputs.1.weight', '')
            ->set('inputs.1.reps', 10)
            ->call('logSet', 1)
            ->assertHasErrors(['inputs.1.weight']);

        $this->assertDatabaseCount('exercise_logs', 0);
    }

    public function test_http_set_endpoints_reject_zero_and_negative_weight(): void
    {
        $this->actingAs($this->client);

        $this->post(route('client.logs.store'), [
            'assignment_id' => $this->assignment->id,
            'routine_day_exercise_id' => $this->routineDayExercise->id,
            'set_number' => 1,
            'weight' => -2.5,
            'reps' => 10,
        ])->assertSessionHasErrors('weight');

        $log = ExerciseLog::create([
            'assignment_id' => $this->assignment->id,
            'routine_day_exercise_id' => $this->routineDayExercise->id,
            'set_number' => 1,
            'weight' => 40,
            'reps' => 10,
            'logged_at' => now(),
        ]);

        $this->put(route('client.logs.update', $log), [
            'set_number' => 1,
            'weight' => 0,
            'reps' => 10,
        ])->assertSessionHasErrors('weight');

        $this->assertSame('40.00', $log->fresh()->weight);
        $this->assertDatabaseCount('exercise_logs', 1);
    }

    public function test_client_cannot_log_set_for_another_clients_assignment()
    {
        $otherClient = User::factory()->create(['gym_id' => $this->gym->id]);
        $this->actingAs($otherClient);
        
        Livewire::test('client.workout-logger', ['assignment' => $this->assignment])
            ->call('selectDay', $this->day->id)
            ->set('inputs.1.weight', 80)
            ->set('inputs.1.reps', 10)
            ->call('logSet', 1)
            ->assertForbidden();
    }

    public function test_client_cannot_log_invalid_set_number()
    {
        $this->actingAs($this->client);
        
        Livewire::test('client.workout-logger', ['assignment' => $this->assignment])
            ->call('selectDay', $this->day->id)
            ->call('logSet', 99) // Invalid set number
            ->assertHasErrors('set_99');
    }

    public function test_tutorial_button_shows_when_media_exists()
    {
        $this->actingAs($this->client);
        
        $this->exercise->update(['gif_url' => 'https://example.com/test.gif']);

        Livewire::test('client.workout-logger', ['assignment' => $this->assignment])
            ->call('selectDay', $this->day->id)
            ->assertSee('Tutorial');
    }

    public function test_tutorial_button_hidden_when_no_media()
    {
        $this->actingAs($this->client);
        
        $this->exercise->update(['gif_url' => null, 'video_url' => null]);

        Livewire::test('client.workout-logger', ['assignment' => $this->assignment])
            ->call('selectDay', $this->day->id)
            ->assertDontSee('Tutorial');
    }

    public function test_client_cannot_log_set_in_historical_routine()
    {
        $this->actingAs($this->client);
        
        $coach = User::factory()->create(['role' => 'coach', 'gym_id' => $this->gym->id]);

        $historical = Assignment::create([
            'gym_id' => $this->gym->id,
            'client_id' => $this->client->id,
            'assigned_by_id' => $coach->id,
            'routine_id' => $this->routine->id,
            'status' => 'completed',
            'end_date' => now()->subDay(),
        ]);

        Livewire::test('client.workout-logger', ['assignment' => $historical])
            ->call('selectDay', $this->day->id)
            ->set('inputs.1.weight', 80)
            ->set('inputs.1.reps', 10)
            ->call('logSet', 1)
            ->assertForbidden();
    }

    public function test_client_can_log_set_in_replay_session()
    {
        $this->actingAs($this->client);
        
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

    public function test_client_cannot_log_set_in_expired_replay()
    {
        $this->actingAs($this->client);
        
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

        Livewire::test('client.workout-logger', ['assignment' => $replay])
            ->call('selectDay', $this->day->id)
            ->set('inputs.1.weight', 80)
            ->set('inputs.1.reps', 10)
            ->call('logSet', 1)
            ->assertForbidden();
    }

    public function test_client_can_log_89_repetitions_when_entered_directly()
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

    public function test_client_cannot_log_more_than_100_repetitions()
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

    public function test_client_routine_layout_loads_livewire_scripts_for_mobile_controls()
    {
        $this->actingAs($this->client);

        $this->get(route('client.routines.active'))
            ->assertOk()
            ->assertSee('livewire/livewire.js', false);
    }

}
