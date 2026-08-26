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
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;

class ClientProgressTest extends TestCase
{
    use RefreshDatabase;

    private function createClient($gymId)
    {
        return User::create([
            'name' => 'Client',
            'email' => uniqid().'@test.com',
            'password' => Hash::make('password'),
            'role' => 'client',
            'gym_id' => $gymId,
        ]);
    }

    public function test_client_can_view_progress_without_history()
    {
        $gym = Gym::create(['name' => 'Test Gym', 'invite_code' => uniqid()]);
        $client = $this->createClient($gym->id);
        $exercise = Exercise::create(['title' => 'Sentadilla', 'muscle_group' => 'piernas', 'is_global' => true]);

        $response = $this->actingAs($client)->get(route('client.progress.exercise', $exercise));
        
        $response->assertStatus(200);
        $response->assertSee('Sentadilla');
        $response->assertSee('no hay registros para este ejercicio');
    }

    public function test_client_cannot_see_other_clients_progress()
    {
        $gym = Gym::create(['name' => 'Test Gym', 'invite_code' => uniqid()]);
        $client1 = $this->createClient($gym->id);
        $client2 = $this->createClient($gym->id);
        
        $exercise = Exercise::create(['title' => 'Press', 'muscle_group' => 'pecho', 'is_global' => true]);
        $routine = Routine::create(['gym_id' => $gym->id, 'title' => 'Rutina', 'coach_id' => $client1->id]);
        $day = RoutineDay::create(['routine_id' => $routine->id, 'day_number' => 1, 'title' => 'Dia 1']);
        $rdExercise = RoutineDayExercise::create(['routine_day_id' => $day->id, 'exercise_id' => $exercise->id, 'order' => 1, 'sets' => 3, 'reps' => '10']);
        
        $assignment2 = Assignment::create([
            'gym_id' => $gym->id,
            'client_id' => $client2->id,
            'routine_id' => $routine->id,
            'assigned_by_id' => $client1->id,
            'is_active' => true,
        ]);

        ExerciseLog::create([
            'assignment_id' => $assignment2->id,
            'routine_day_exercise_id' => $rdExercise->id,
            'set_number' => 1,
            'weight' => 100,
            'reps' => 10,
            'logged_at' => now(),
        ]);

        $response = $this->actingAs($client1)->get(route('client.progress.exercise', $exercise));
        $response->assertStatus(200);
        $response->assertDontSee('100 kg'); // shouldn't see client2's logs
    }

    public function test_progress_shows_records_and_calculates_pr()
    {
        $gym = Gym::create(['name' => 'Test Gym', 'invite_code' => uniqid()]);
        $client = $this->createClient($gym->id);
        
        $exercise = Exercise::create(['title' => 'Peso Muerto', 'muscle_group' => 'espalda', 'is_global' => true]);
        $routine = Routine::create(['gym_id' => $gym->id, 'title' => 'Rutina', 'coach_id' => $client->id]);
        $day = RoutineDay::create(['routine_id' => $routine->id, 'day_number' => 1, 'title' => 'Dia 1']);
        $rdExercise = RoutineDayExercise::create(['routine_day_id' => $day->id, 'exercise_id' => $exercise->id, 'order' => 1, 'sets' => 3, 'reps' => '10']);
        
        $assignment = Assignment::create([
            'gym_id' => $gym->id,
            'client_id' => $client->id,
            'routine_id' => $routine->id,
            'assigned_by_id' => $client->id,
            'is_active' => true,
        ]);

        ExerciseLog::create([
            'assignment_id' => $assignment->id,
            'routine_day_exercise_id' => $rdExercise->id,
            'set_number' => 1,
            'weight' => 80,
            'reps' => 5,
            'logged_at' => now()->subDays(2),
        ]);

        ExerciseLog::create([
            'assignment_id' => $assignment->id,
            'routine_day_exercise_id' => $rdExercise->id,
            'set_number' => 1,
            'weight' => 90,
            'reps' => 3,
            'logged_at' => now(),
        ]);

        $response = $this->actingAs($client)->get(route('client.progress.exercise', $exercise));
        $response->assertStatus(200);
        $response->assertSee('90'); // PR
        $response->assertSee('80'); // Historial
    }
}
