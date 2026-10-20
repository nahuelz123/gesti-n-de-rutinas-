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

class ClientCoachJourneyTest extends TestCase
{
    use RefreshDatabase;

    public function test_coach_to_client_training_journey_works_end_to_end(): void
    {
        $gym = Gym::create(['name' => 'Journey Gym']);
        $coach = User::factory()->create(['role' => 'coach', 'gym_id' => $gym->id]);
        $client = User::factory()->create(['role' => 'client', 'gym_id' => $gym->id]);

        $this->actingAs($coach);

        $routine = Routine::create([
            'title' => 'Rutina QA',
            'description' => 'Recorrido completo',
        ]);

        $exercise = Exercise::create([
            'title' => 'Press banca QA',
            'muscle_group' => 'pecho',
            'is_global' => false,
        ]);

        $day = RoutineDay::create([
            'routine_id' => $routine->id,
            'day_number' => 1,
            'title' => 'Día 1',
        ]);

        $routineExercise = RoutineDayExercise::create([
            'routine_day_id' => $day->id,
            'exercise_id' => $exercise->id,
            'sets' => 3,
            'reps' => '8-10',
            'rest' => 90,
            'order' => 1,
        ]);

        $assignment = Assignment::create([
            'routine_id' => $routine->id,
            'client_id' => $client->id,
            'assigned_by_id' => $coach->id,
            'assigned_at' => now(),
            'status' => 'active',
        ]);

        $this->assertSame($gym->id, $routine->gym_id);
        $this->assertSame($coach->id, $routine->coach_id);
        $this->assertSame($gym->id, $exercise->gym_id);
        $this->assertSame($gym->id, $assignment->gym_id);

        $this->get('/admin')->assertOk();
        $this->get(route('client.dashboard'))->assertForbidden();

        $this->actingAs($client);

        $this->get(route('dashboard'))
            ->assertRedirect(route('client.dashboard'));

        $this->get(route('client.dashboard'))
            ->assertOk()
            ->assertSee('Rutina QA');

        $this->get(route('client.routines.active'))
            ->assertOk()
            ->assertSee('Rutina QA');

        $this->get(route('client.routines.show', $assignment))
            ->assertOk()
            ->assertSee('Press banca QA');

        $this->get('/admin')->assertForbidden();

        Livewire::test('client.workout-logger', ['assignment' => $assignment])
            ->call('selectDay', $day->id)
            ->set('inputs.1.weight', '80')
            ->set('inputs.1.reps', '10')
            ->call('logSet', 1)
            ->assertHasNoErrors()
            ->assertDispatched('set-logged');

        $this->assertDatabaseHas('exercise_logs', [
            'assignment_id' => $assignment->id,
            'routine_day_exercise_id' => $routineExercise->id,
            'set_number' => 1,
            'weight' => 80,
            'reps' => 10,
        ]);

        $this->get(route('client.progress.index'))
            ->assertOk();

        $this->get(route('client.progress.exercise', $exercise))
            ->assertOk()
            ->assertSee('80');

        $this->assertSame(1, ExerciseLog::query()->where('assignment_id', $assignment->id)->count());
    }

    public function test_coach_cannot_open_client_area_and_client_cannot_open_coach_area(): void
    {
        $gym = Gym::create(['name' => 'Role separation']);
        $coach = User::factory()->create(['role' => 'coach', 'gym_id' => $gym->id]);
        $client = User::factory()->create(['role' => 'client', 'gym_id' => $gym->id]);

        $this->actingAs($coach);
        $this->get(route('client.dashboard'))->assertForbidden();

        $this->actingAs($client);
        $this->get('/admin')->assertForbidden();
    }

    public function test_client_cannot_access_another_clients_assignment_even_by_guessing_the_id(): void
    {
        $gym = Gym::create(['name' => 'Tenant isolation']);
        $coach = User::factory()->create(['role' => 'coach', 'gym_id' => $gym->id]);
        $owner = User::factory()->create(['role' => 'client', 'gym_id' => $gym->id]);
        $intruder = User::factory()->create(['role' => 'client', 'gym_id' => $gym->id]);

        $this->actingAs($coach);
        $routine = Routine::create(['title' => 'Privada']);
        $assignment = Assignment::create([
            'routine_id' => $routine->id,
            'client_id' => $owner->id,
            'assigned_by_id' => $coach->id,
            'assigned_at' => now(),
            'status' => 'active',
        ]);

        $this->actingAs($intruder);

        $this->get(route('client.routines.show', $assignment))->assertNotFound();
        $this->get(route('client.routines.session', $assignment))->assertNotFound();
    }
}
