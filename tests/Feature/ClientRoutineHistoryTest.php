<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Exercise;
use App\Models\Gym;
use App\Models\Routine;
use App\Models\RoutineDay;
use App\Models\RoutineDayExercise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientRoutineHistoryTest extends TestCase
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

        $this->otherClient = User::factory()->create([
            'role' => 'client',
            'gym_id' => $this->gym->id,
        ]);
        
        $this->routine = Routine::create([
            'gym_id' => $this->gym->id,
            'coach_id' => $this->client->id,
            'title' => 'Rutina Historica',
        ]);
        
        $this->day = RoutineDay::create([
            'routine_id' => $this->routine->id,
            'day_number' => 1,
            'title' => 'Día 1',
        ]);
        
        $this->exercise = Exercise::create([
            'gym_id' => $this->gym->id,
            'title' => 'Press Militar',
            'muscle_group' => 'hombros',
            'gif_url' => 'https://example.com/test.gif',
        ]);
        
        $this->routineDayExercise = RoutineDayExercise::create([
            'routine_day_id' => $this->day->id,
            'exercise_id' => $this->exercise->id,
            'sets' => 3,
            'reps' => '10',
            'order' => 1,
        ]);
        
        $this->coach = User::factory()->create([
            'role' => 'coach',
            'gym_id' => $this->gym->id,
        ]);
        
        $this->assignment = Assignment::create([
            'gym_id' => $this->gym->id,
            'client_id' => $this->client->id,
            'assigned_by_id' => $this->coach->id,
            'routine_id' => $this->routine->id,
            'status' => 'completed',
            'end_date' => now()->subDay(),
            'assigned_at' => now()->subMonth(),
        ]);
    }

    public function test_client_can_view_own_history()
    {
        $response = $this->actingAs($this->client)->get(route('client.routines.history'));
        $response->assertStatus(200);
        $response->assertSee('Rutina Historica');
        $response->assertSee('completed');
    }

    public function test_client_cannot_see_other_clients_history()
    {
        $response = $this->actingAs($this->otherClient)->get(route('client.routines.history'));
        $response->assertStatus(200);
        $response->assertDontSee('Rutina Historica');
    }

    public function test_client_can_view_historical_routine_detail()
    {
        $response = $this->actingAs($this->client)->get(route('client.routines.show', $this->assignment));
        $response->assertStatus(200);
        $response->assertSee('Rutina Historica');
        $response->assertSee('Día 1');
        $response->assertSee('Press Militar');
        $response->assertSee('Ver tutorial');
        $response->assertSee('Realizar rutina');
        $response->assertSee(route('client.progress.exercise', $this->exercise->id));
    }

    public function test_client_cannot_view_other_clients_historical_routine_detail()
    {
        $response = $this->actingAs($this->otherClient)->get(route('client.routines.show', $this->assignment));
        $response->assertStatus(404);
    }

    public function test_client_can_initiate_replay_session()
    {
        $response = $this->actingAs($this->client)->post(route('client.routines.replay', $this->assignment));
        
        $replayAssignment = Assignment::query()
            ->where('client_id', $this->client->id)
            ->where('routine_id', $this->assignment->routine_id)
            ->whereDate('start_date', today())
            ->whereNotNull('end_date')
            ->first();

        $this->assertNotNull($replayAssignment);
        $this->assertEquals('completed', $replayAssignment->status);
        $this->assertTrue(\Carbon\Carbon::parse($replayAssignment->start_date)->isToday());
        
        $response->assertRedirect(route('client.routines.session', $replayAssignment));
        
        // original assignment is intact
        $this->assignment->refresh();
        $this->assertTrue(\Carbon\Carbon::parse($this->assignment->end_date)->isBefore(today()));
    }

    public function test_client_cannot_initiate_replay_from_other_clients_routine()
    {
        $response = $this->actingAs($this->otherClient)->post(route('client.routines.replay', $this->assignment));
        $response->assertStatus(404);
    }
}
