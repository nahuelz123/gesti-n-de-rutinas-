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

class ExperiencePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_experience_routes_are_unavailable_when_flag_is_disabled(): void
    {
        config()->set('experience.enabled', false);
        $this->get('/experiencia')->assertNotFound();
        $this->get('/experiencia/personaje')->assertNotFound();
        $this->actingAs(User::factory()->create(['role' => 'client']))
            ->get('/app/personaje')->assertNotFound();
    }

    public function test_public_demo_uses_only_fictional_data_and_never_registers_a_user(): void
    {
        config()->set('experience.enabled', true);
        $response = $this->get('/experiencia');
        $response->assertOk()->assertViewIs('experience.overview')
            ->assertViewHas('isDemo', true)
            ->assertSee('Demo')->assertSee('datos de ejemplo');
        $this->get('/experiencia/personaje')->assertOk()
            ->assertViewIs('experience.character')->assertViewHas('isDemo', true);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('exercise_logs', 0);
    }

    public function test_client_character_requires_authentication_and_client_role(): void
    {
        config()->set('experience.enabled', true);
        $this->get('/app/personaje')->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => 'coach']))
            ->get('/app/personaje')->assertForbidden();
    }

    public function test_enabled_client_dashboard_uses_own_real_data_and_escapes_names(): void
    {
        config()->set('experience.enabled', true);
        [$client, $assignment, $slot] = $this->workout();
        $client->update(['name' => '<script>alert(1)</script>']);
        ExerciseLog::create(['assignment_id' => $assignment->id, 'routine_day_exercise_id' => $slot->id,
            'exercise_id' => $slot->exercise_id, 'set_number' => 1, 'weight' => 40, 'reps' => 10, 'logged_at' => now()]);
        $this->actingAs($client)->get('/app')->assertOk()
            ->assertViewIs('experience.overview')->assertViewHas('isDemo', false)
            ->assertViewHas('experience', fn ($data) => $data['xp'] === 100)
            ->assertDontSee('<script>alert(1)</script>', false);
        $this->get('/app/personaje')->assertOk()->assertViewIs('experience.character')
            ->assertViewHas('avatarStorageKey', 'visionfit:avatar:'.$client->id);
    }

    public function test_disabled_flag_keeps_the_existing_dashboard_and_measurement_page(): void
    {
        config()->set('experience.enabled', false);
        [$client] = $this->workout();
        $this->actingAs($client)->get('/app')->assertOk()->assertViewIs('client.dashboard');
        $this->get('/app/progress')->assertOk()->assertViewIs('client.progress.index');
    }

    public function test_character_handles_an_empty_history_and_missing_routine(): void
    {
        config()->set('experience.enabled', true);
        $client = User::factory()->create(['role' => 'client']);
        $this->actingAs($client)->get('/app/personaje')->assertOk()
            ->assertViewHas('experience', fn ($data) => $data['xp'] === 0 && $data['level'] === 1)
            ->assertViewHas('workoutTitle', 'Tu próximo entrenamiento');
    }

    public function test_workout_map_tracks_the_actual_replacement_exercise(): void
    {
        config()->set('experience.enabled', true);
        [$client, $assignment, $slot] = $this->workout();
        $replacement = Exercise::create(['title' => 'Curl de bíceps', 'muscle_group' => 'Bíceps', 'is_global' => true]);
        $this->actingAs($client);
        Livewire::test('client.workout-logger', ['assignment' => $assignment])
            ->call('selectDay', $slot->routine_day_id)
            ->assertSee('Músculos del ejercicio')
            ->call('replaceExercise', $replacement->id)
            ->assertSee('Curl de bíceps')
            ->assertSee('data-primary="biceps"', false);
    }

    public function test_workout_avatar_keeps_the_clients_unlocked_themes(): void
    {
        config()->set('experience.enabled', true);
        [$client, $assignment, $slot] = $this->workout();
        foreach (range(1, 10) as $daysAgo) {
            ExerciseLog::create(['assignment_id' => $assignment->id, 'routine_day_exercise_id' => $slot->id,
                'exercise_id' => $slot->exercise_id, 'set_number' => 1, 'weight' => 40, 'reps' => 10,
                'logged_at' => now()->subDays($daysAgo)]);
        }
        $this->actingAs($client);
        Livewire::test('client.workout-logger', ['assignment' => $assignment])
            ->assertSet('experienceLevel', 3);
    }

    public function test_week_calendar_keeps_valid_activity_from_archived_assignments(): void
    {
        config()->set('experience.enabled', true);
        $this->travelTo(now()->startOfWeek()->addDays(2)->setTime(12, 0));
        [$client, $assignment, $slot] = $this->workout();
        ExerciseLog::create(['assignment_id' => $assignment->id, 'routine_day_exercise_id' => $slot->id,
            'exercise_id' => $slot->exercise_id, 'set_number' => 1, 'weight' => 40, 'reps' => 10,
            'logged_at' => now()->startOfWeek()->addHours(12)]);
        $assignment->delete();
        $this->actingAs($client)->get('/app')->assertOk()
            ->assertViewHas('weekActivity', fn ($days) => $days[0]['active'] === true)
            ->assertViewHas('experience', fn ($data) => $data['week_days'] === 1);
    }

    private function workout(): array
    {
        $gym = Gym::create(['name' => 'Preview gym']);
        $client = User::factory()->create(['role' => 'client', 'gym_id' => $gym->id]);
        $routine = Routine::create(['gym_id' => $gym->id, 'coach_id' => $client->id, 'title' => 'Torso A']);
        $day = RoutineDay::create(['routine_id' => $routine->id, 'title' => 'Pecho', 'day_number' => 1]);
        $exercise = Exercise::create(['title' => 'Press banca', 'muscle_group' => 'Pecho', 'is_global' => true]);
        $slot = RoutineDayExercise::create(['routine_day_id' => $day->id, 'exercise_id' => $exercise->id, 'sets' => 3, 'reps' => '8-10', 'rest' => 90, 'order' => 1]);
        $assignment = Assignment::create(['gym_id' => $gym->id, 'routine_id' => $routine->id, 'client_id' => $client->id, 'status' => 'active', 'assigned_at' => now()]);

        return [$client, $assignment, $slot];
    }
}
