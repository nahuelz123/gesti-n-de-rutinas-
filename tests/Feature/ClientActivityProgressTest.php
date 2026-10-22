<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Assignment;
use App\Models\BodyMeasurement;
use App\Models\Exercise;
use App\Models\ExerciseLog;
use App\Models\Gym;
use App\Models\Routine;
use App\Models\RoutineDay;
use App\Models\RoutineDayExercise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClientActivityProgressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 20)->setTime(18, 0));
    }

    public function test_steps_use_a_disclosed_time_estimate_and_add_to_day_and_week_once(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $payload = $this->activity(['activity_key' => 'walk_steps', 'step_count' => 3000,
            'untracked_steps' => 1, 'duration_minutes' => 600, 'active_kcal' => 9999]);
        $this->actingAs($client)->post('/app/activities', $payload)->assertSessionHasNoErrors();
        $this->post('/app/activities', $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('activity_logs', 1);
        $log = ActivityLog::firstOrFail();
        $this->assertSame(3000, $log->step_count);
        $this->assertSame(30, $log->duration_minutes);
        $this->assertEquals(80, $log->active_kcal); // 3000 / 100 = 30 minutes; (3 - 1) * 80 * 0.5.
        $response = $this->get('/app/activities')->assertOk()->assertSee('Pasos');
        $this->assertSame(3000, $response->viewData('daySummary')['steps']);
        $this->assertSame(3000, $response->viewData('weekSummary')['steps']);
        $this->assertSame(80.0, $response->viewData('daySummary')['total_kcal']);
    }

    public function test_steps_reject_missing_invalid_or_already_counted_steps(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'client']));
        foreach ([null, 0, -1, 3.5, 60001] as $steps) {
            $this->post('/app/activities', $this->activity(['activity_key' => 'walk_steps',
                'step_count' => $steps, 'untracked_steps' => 1]))->assertSessionHasErrors('step_count');
        }
        $this->post('/app/activities', $this->activity(['activity_key' => 'walk_steps',
            'step_count' => 3000, 'untracked_steps' => 0]))->assertSessionHasErrors('untracked_steps');
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_editing_steps_to_a_timed_activity_clears_steps_and_recalculates(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'client']))->post('/app/activities',
            $this->activity(['activity_key' => 'walk_steps', 'step_count' => 3000, 'untracked_steps' => 1]))
            ->assertSessionHasNoErrors();
        $log = ActivityLog::firstOrFail();
        $this->put('/app/activities/'.$log->id, $this->activity(['duration_minutes' => 30, 'step_count' => 9999]))
            ->assertSessionHasNoErrors();
        $this->assertNull($log->fresh()->step_count);
        $this->get('/app/activities')->assertOk()->assertViewHas('daySummary', fn ($day) => $day['steps'] === 0 && $day['total_kcal'] === 160.0);
    }

    public function test_logged_sets_estimate_strength_using_body_weight_and_prescribed_rest(): void
    {
        [$client, $assignment, $row] = $this->workout();
        BodyMeasurement::create(['client_id' => $client->id, 'measured_at' => '2026-10-19', 'weight' => 80]);
        $this->sets($assignment, $row, '2026-10-20 12:00:00', 3);
        $response = $this->actingAs($client)->get('/app/activities')->assertOk();
        $day = $response->viewData('daySummary');
        $this->assertSame(3, $day['estimated_strength_sets']);
        $this->assertSame(5, $day['strength_minutes']); // 3 * 10 * 3 seconds + 2 * 90 seconds = 4.5 minutes, rounded up.
        $this->assertSame(16.67, $day['strength_kcal']);
        $this->assertSame(16.67, $response->viewData('weekSummary')['total_kcal']);
        $response->assertSee('Estimación desde tus series');
        $this->assertDatabaseCount('activity_logs', 0); // Viewing a summary must not write activities.
    }

    public function test_manual_strength_replaces_the_same_days_automatic_estimate_and_delete_restores_it(): void
    {
        [$client, $assignment, $row] = $this->workout();
        BodyMeasurement::create(['client_id' => $client->id, 'measured_at' => '2026-10-19', 'weight' => 80]);
        $this->sets($assignment, $row, '2026-10-20 12:00:00', 3);
        $this->actingAs($client)->post('/app/activities', $this->activity(['activity_key' => 'strength_general', 'duration_minutes' => 60]))
            ->assertSessionHasNoErrors();
        $this->get('/app/activities')->assertOk()->assertViewHas('daySummary', fn ($day) => $day['strength_kcal'] === 200.0 && $day['estimated_strength_sets'] === 0);
        $this->delete('/app/activities/'.ActivityLog::firstOrFail()->id)->assertRedirect();
        $this->get('/app/activities')->assertOk()->assertViewHas('daySummary', fn ($day) => $day['strength_kcal'] === 16.67);
    }

    public function test_automatic_strength_ignores_future_weights_and_foreign_or_invalid_sets(): void
    {
        [$client, $assignment, $row] = $this->workout();
        [$other, $otherAssignment, $otherRow] = $this->workout();
        $this->sets($assignment, $row, '2026-10-20 12:00:00', 3);
        $this->sets($otherAssignment, $otherRow, '2026-10-20 12:00:00', 5);
        $this->sets($assignment, $row, '2026-10-21 12:00:00', 2);
        BodyMeasurement::create(['client_id' => $other->id, 'measured_at' => '2026-10-19', 'weight' => 150]);
        BodyMeasurement::create(['client_id' => $client->id, 'measured_at' => '2026-10-21', 'weight' => 90]);
        $this->actingAs($client)->get('/app/activities')->assertOk()->assertViewHas('daySummary', fn ($day) => $day['strength_kcal'] === 0.0 && $day['unestimated_strength_sets'] === 3);
        BodyMeasurement::create(['client_id' => $client->id, 'measured_at' => '2026-10-19', 'weight' => 80]);
        ExerciseLog::where('assignment_id', $assignment->id)->where('set_number', 3)->whereDate('logged_at', '2026-10-20')->update(['reps' => 0]);
        $this->get('/app/activities')->assertOk()->assertViewHas('daySummary', fn ($day) => $day['estimated_strength_sets'] === 2 && $day['strength_minutes'] === 3 && $day['strength_kcal'] === 10.0);
    }

    public function test_historical_estimates_use_the_weight_available_on_each_day(): void
    {
        [$client, $assignment, $row] = $this->workout();
        BodyMeasurement::create(['client_id' => $client->id, 'measured_at' => '2026-10-19', 'weight' => 80]);
        BodyMeasurement::create(['client_id' => $client->id, 'measured_at' => '2026-10-20', 'weight' => 84]);
        $this->sets($assignment, $row, '2026-10-19 12:00:00', 3);
        $this->sets($assignment, $row, '2026-10-20 12:00:00', 3);
        $response = $this->actingAs($client)->get('/app/activities?date=2026-10-19')->assertOk();
        $this->assertSame(16.67, $response->viewData('daySummary')['strength_kcal']);
        $this->assertSame(17.5, $response->viewData('weekDays')['2026-10-20']['strength_kcal']);
        $this->assertSame(34.17, $response->viewData('weekSummary')['total_kcal']);
    }

    public function test_progress_feedback_shows_only_the_clients_recorded_week_and_comparable_improvement(): void
    {
        [$client, $assignment, $row] = $this->workout();
        [$other, $otherAssignment, $otherRow] = $this->workout();
        $this->sets($assignment, $row, '2026-10-17 12:00:00', 2, 10);
        $this->sets($assignment, $row, '2026-10-20 12:00:00', 3, 12);
        $this->sets($otherAssignment, $otherRow, '2026-10-18 12:00:00', 5);
        $this->sets($assignment, $row, '2026-10-21 12:00:00', 2);
        $response = $this->actingAs($client)->get('/app/progress')->assertOk()->assertSee('Qué venís haciendo bien')->assertSee('Para mejorar tu seguimiento');
        $feedback = $response->viewData('feedback');
        $this->assertSame(2, $feedback['training_days']);
        $this->assertSame(5, $feedback['sets']);
        $this->assertCount(1, $feedback['improvements']);
        $this->assertSame($row->exercise_id, $feedback['improvements'][0]['exercise_id']);
        $this->assertSame(2, $feedback['improvements'][0]['reps_gain']);
        $this->assertContains('body_weight_missing', array_column($feedback['suggestions'], 'code'));
    }

    public function test_more_weight_with_fewer_reps_is_not_reported_as_a_comparable_improvement(): void
    {
        [$client, $assignment, $row] = $this->workout();
        $this->sets($assignment, $row, '2026-10-17 12:00:00', 1, 10, 60);
        $this->sets($assignment, $row, '2026-10-20 12:00:00', 1, 5, 80);
        $response = $this->actingAs($client)->get('/app/progress')->assertOk();
        $this->assertSame([], $response->viewData('feedback')['improvements']);
    }

    public function test_feedback_does_not_invent_activity_for_an_empty_account(): void
    {
        $response = $this->actingAs(User::factory()->create(['role' => 'client']))->get('/app/progress')->assertOk();
        $feedback = $response->viewData('feedback');
        $this->assertSame(0, $feedback['training_days']);
        $this->assertSame(0, $feedback['sets']);
        $this->assertSame([], $feedback['improvements']);
        $this->assertSame([], $feedback['positives']);
    }

    public function test_a_cardio_exercise_in_a_routine_does_not_generate_strength_calories(): void
    {
        [$client, $assignment, $row] = $this->workout();
        $cardio = Exercise::create(['title' => 'Elíptico', 'muscle_group' => 'cardio', 'is_global' => true]);
        BodyMeasurement::create(['client_id' => $client->id, 'measured_at' => '2026-10-19', 'weight' => 80]);
        $this->sets($assignment, $row, '2026-10-20 12:00:00', 3);
        ExerciseLog::where('assignment_id', $assignment->id)->update(['exercise_id' => $cardio->id]);
        $this->actingAs($client)->post('/app/activities', $this->activity())->assertSessionHasNoErrors();
        $this->get('/app/activities')->assertOk()->assertViewHas('daySummary', fn ($day) => $day['strength_kcal'] === 0.0 && $day['total_kcal'] === 160.0);
        $row->exercise->update(['muscle_group' => 'cardio']);
        ExerciseLog::where('assignment_id', $assignment->id)->update(['exercise_id' => null]);
        $this->get('/app/activities')->assertOk()->assertViewHas('daySummary', fn ($day) => $day['strength_kcal'] === 0.0 && $day['total_kcal'] === 160.0);
    }

    public function test_explicit_zero_rest_is_not_replaced_with_ninety_seconds_in_the_strength_model(): void
    {
        [$client, $assignment, $row] = $this->workout();
        $row->update(['rest' => '0 s']);
        BodyMeasurement::create(['client_id' => $client->id, 'measured_at' => '2026-10-19', 'weight' => 80]);
        $this->sets($assignment, $row, '2026-10-20 12:00:00', 3);
        $this->actingAs($client)->get('/app/activities')->assertOk()->assertViewHas('daySummary', fn ($day) => $day['strength_minutes'] === 2 && $day['strength_kcal'] === 6.67);
    }

    public function test_feedback_keeps_performed_variants_separate(): void
    {
        [$client, $assignment, $row] = $this->workout();
        $otherExercise = Exercise::create(['title' => 'Press inclinado con mancuernas', 'muscle_group' => 'pecho', 'is_global' => true]);
        $this->sets($assignment, $row, '2026-10-17 12:00:00', 1, 10);
        $this->sets($assignment, $row, '2026-10-20 12:00:00', 1, 12);
        ExerciseLog::where('assignment_id', $assignment->id)->whereDate('logged_at', '2026-10-20')->update(['exercise_id' => $otherExercise->id]);
        $response = $this->actingAs($client)->get('/app/progress')->assertOk();
        $this->assertSame([], $response->viewData('feedback')['improvements']);
    }

    private function activity(array $overrides = []): array
    {
        return array_replace(['performed_on' => '2026-10-20', 'activity_key' => 'elliptical_moderate',
            'duration_minutes' => 30, 'body_weight' => 80, 'request_token' => (string) Str::uuid()], $overrides);
    }

    private function workout(): array
    {
        $gym = Gym::create(['name' => 'Gym seguimiento']);
        $client = User::factory()->create(['role' => 'client', 'gym_id' => $gym->id]);
        $routine = Routine::create(['gym_id' => $gym->id, 'coach_id' => $client->id, 'title' => 'Rutina']);
        $day = RoutineDay::create(['routine_id' => $routine->id, 'title' => 'Torso', 'day_number' => 1]);
        $exercise = Exercise::create(['title' => 'Press de pecho', 'muscle_group' => 'pecho', 'is_global' => true]);
        $row = RoutineDayExercise::create(['routine_day_id' => $day->id, 'exercise_id' => $exercise->id,
            'sets' => 5, 'reps' => '8-12', 'rest' => '90 s', 'order' => 1]);
        $assignment = Assignment::create(['gym_id' => $gym->id, 'client_id' => $client->id, 'routine_id' => $routine->id,
            'status' => 'active', 'assigned_at' => now()]);

        return [$client, $assignment, $row];
    }

    private function sets(Assignment $assignment, RoutineDayExercise $row, string $date, int $sets, int $reps = 10, float $weight = 60): void
    {
        for ($number = 1; $number <= $sets; $number++) {
            ExerciseLog::create(['assignment_id' => $assignment->id, 'routine_day_exercise_id' => $row->id,
                'set_number' => $number, 'weight' => $weight, 'reps' => $reps, 'logged_at' => $date]);
        }
    }
}
