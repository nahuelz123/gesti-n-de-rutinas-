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
use App\Services\ExerciseSetRecorder;
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
            ->assertDispatched('set-logged', set: 1, nextSet: 2, rest: 90);

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
            'end_date' => now()->subDay(),
        ]);

        ExerciseLog::create([
            'assignment_id' => $oldAssignment->id,
            'routine_day_exercise_id' => $oldRde->id,
            'set_number' => 1,
            'weight' => 70,
            'reps' => 8,
            'logged_at' => now()->subDay()->setTime(18, 0),
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
            ->assertSee('1 min 30 s descanso')
            ->assertSee('vf-rest-timer', false)
            ->assertSee('@set-logged.window', false)
            ->assertSee('OMITIR');
    }

    public function test_two_minute_rest_is_rendered_without_duplicate_units(): void
    {
        $this->routineDayExercise->update(['rest' => 120]);
        $this->actingAs($this->client);

        Livewire::test('client.workout-logger', ['assignment' => $this->assignment])
            ->call('selectDay', $this->day->id)
            ->assertSee('2 min descanso')
            ->assertDontSee('2 min s descanso');
    }

    public function test_client_can_copy_previous_set_values(): void
    {
        $this->actingAs($this->client);

        Livewire::test('client.workout-logger', ['assignment' => $this->assignment])
            ->call('selectDay', $this->day->id)
            ->set('inputs.1.weight', '42.5')
            ->set('inputs.1.reps', '8')
            ->call('copyPreviousSet', 2)
            ->assertSet('inputs.2.weight', '42.5')
            ->assertSet('inputs.2.reps', '8');
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

    public function test_livewire_rejects_a_set_beyond_the_prescribed_amount(): void
    {
        $this->actingAs($this->client);

        Livewire::test('client.workout-logger', ['assignment' => $this->assignment])
            ->call('selectDay', $this->day->id)
            ->set('inputs.4.weight', '80')
            ->set('inputs.4.reps', '10')
            ->call('logSet', 4)
            ->assertHasErrors('set_4');

        $this->assertDatabaseCount('exercise_logs', 0);
    }

    public function test_http_endpoint_rejects_a_set_beyond_the_prescribed_amount(): void
    {
        $this->actingAs($this->client);

        $this->post(route('client.logs.store'), [
            'assignment_id' => $this->assignment->id,
            'routine_day_exercise_id' => $this->routineDayExercise->id,
            'set_number' => 4,
            'weight' => 80,
            'reps' => 10,
        ])->assertUnprocessable();

        $this->assertDatabaseCount('exercise_logs', 0);
    }

    public function test_http_and_livewire_update_the_same_set_instead_of_creating_duplicates(): void
    {
        $this->actingAs($this->client);

        $this->post(route('client.logs.store'), [
            'assignment_id' => $this->assignment->id,
            'routine_day_exercise_id' => $this->routineDayExercise->id,
            'set_number' => 1,
            'weight' => 60,
            'reps' => 10,
        ])->assertSessionDoesntHaveErrors();

        Livewire::test('client.workout-logger', ['assignment' => $this->assignment])
            ->call('selectDay', $this->day->id)
            ->set('inputs.1.weight', 70)
            ->set('inputs.1.reps', 12)
            ->call('logSet', 1)
            ->assertDispatched('set-logged');

        $this->assertDatabaseCount('exercise_logs', 1);
        $this->assertDatabaseHas('exercise_logs', [
            'assignment_id' => $this->assignment->id,
            'routine_day_exercise_id' => $this->routineDayExercise->id,
            'set_number' => 1,
            'weight' => 70,
            'reps' => 12,
        ]);
    }

    public function test_replacement_is_saved_for_today_without_changing_the_coach_routine(): void
    {
        $alternative = Exercise::create(['gym_id' => $this->gym->id, 'title' => 'Press con mancuernas', 'muscle_group' => 'pecho']);
        $this->actingAs($this->client);
        Livewire::test('client.workout-logger', ['assignment' => $this->assignment])
            ->call('selectDay', $this->day->id)
            ->call('replaceExercise', $alternative->id)
            ->assertSee('Original del entrenador: Press Banca')
            ->set('inputs.1.weight', 24)->set('inputs.1.reps', 10)->call('logSet', 1);
        $this->assertDatabaseHas('exercise_logs', ['exercise_id' => $alternative->id, 'routine_day_exercise_id' => $this->routineDayExercise->id]);
        $this->assertEquals($this->exercise->id, $this->routineDayExercise->fresh()->exercise_id);
        $this->get(route('client.progress.exercise', ['exercise' => $alternative->id, 'assignment' => $this->assignment->id, 'day' => $this->day->id, 'routine_exercise' => $this->routineDayExercise->id]))->assertOk()->assertSee('Volver al entrenamiento');
        Livewire::test('client.workout-logger', ['assignment' => $this->assignment])
            ->call('selectDay', $this->day->id)->assertSee('Press con mancuernas');
        $this->travel(1)->days();
        Livewire::test('client.workout-logger', ['assignment' => $this->assignment])
            ->call('selectDay', $this->day->id)->assertDontSee('Original del entrenador:');
    }

    public function test_replacement_rejects_foreign_and_deleted_catalog_exercises(): void
    {
        $foreign = Exercise::create(['gym_id' => Gym::create(['name' => 'Other'])->id, 'title' => 'Private press', 'muscle_group' => 'pecho']);
        $deleted = Exercise::create(['gym_id' => $this->gym->id, 'title' => 'Deleted press', 'muscle_group' => 'pecho']);
        $deleted->delete();
        $this->actingAs($this->client);
        foreach ([$foreign, $deleted] as $exercise) {
            Livewire::test('client.workout-logger', ['assignment' => $this->assignment])
                ->call('selectDay', $this->day->id)->call('replaceExercise', $exercise->id)->assertForbidden();
        }
    }

    public function test_replacement_cannot_relabel_previously_saved_sets(): void
    {
        $alternative = Exercise::create(['gym_id' => $this->gym->id, 'title' => 'Alternative', 'muscle_group' => 'pecho']);
        $this->actingAs($this->client);
        Livewire::test('client.workout-logger', ['assignment' => $this->assignment])
            ->call('selectDay', $this->day->id)
            ->set('inputs.1.reps', 10)->call('logSet', 1)
            ->call('replaceExercise', $alternative->id)->assertHasErrors('replacement');
        $this->assertDatabaseHas('exercise_logs', ['exercise_id' => $this->exercise->id]);
        $this->assertDatabaseCount('workout_exercise_replacements', 0);
    }

    public function test_progress_uses_actual_exercise_with_legacy_log_fallback(): void
    {
        $alternative = Exercise::create(['gym_id' => $this->gym->id, 'title' => 'Alternative', 'muscle_group' => 'pecho']);
        $base = ['assignment_id' => $this->assignment->id, 'routine_day_exercise_id' => $this->routineDayExercise->id, 'set_number' => 1, 'reps' => 8, 'logged_at' => now()->subDay()];
        ExerciseLog::create($base + ['exercise_id' => $alternative->id, 'weight' => 24]);
        ExerciseLog::create(array_replace($base, ['weight' => 70, 'set_number' => 2]));
        $this->actingAs($this->client);
        $this->get(route('client.progress.exercise', $alternative))->assertOk()->assertViewHas('logs', fn ($logs) => $logs->count() === 1 && (float) $logs->first()->weight === 24.0);
        $this->get(route('client.progress.exercise', $this->exercise))->assertOk()->assertViewHas('logs', fn ($logs) => $logs->count() === 1 && (float) $logs->first()->weight === 70.0);
    }

    public function test_alternatives_are_searchable_and_recommend_same_muscle_without_exposing_other_gyms(): void
    {
        Exercise::create(['gym_id' => $this->gym->id, 'title' => 'Press recomendado', 'muscle_group' => 'pecho']);
        Exercise::create(['gym_id' => $this->gym->id, 'title' => 'Sentadilla visible', 'muscle_group' => 'piernas']);
        Exercise::create(['gym_id' => Gym::create(['name' => 'Other'])->id, 'title' => 'Press secreto', 'muscle_group' => 'pecho']);
        $this->actingAs($this->client);
        Livewire::test('client.workout-logger', ['assignment' => $this->assignment])
            ->call('selectDay', $this->day->id)->call('toggleAlternatives')
            ->assertSee('Press recomendado')->assertSee('Mismo grupo muscular')->assertDontSee('Press secreto')
            ->set('alternativeSearch', 'Sentadilla')->assertSee('Sentadilla visible')->assertDontSee('Press recomendado');
    }

    public function test_replacement_requires_an_editable_assignment(): void
    {
        $alternative = Exercise::create(['gym_id' => $this->gym->id, 'title' => 'Alternative', 'muscle_group' => 'pecho']);
        $this->assignment->update(['status' => 'completed', 'end_date' => today()->subDay()]);
        $this->actingAs($this->client);
        Livewire::test('client.workout-logger', ['assignment' => $this->assignment])
            ->call('selectDay', $this->day->id)->call('replaceExercise', $alternative->id)->assertForbidden();
    }

    public function test_replacement_does_not_change_another_day_or_assignment(): void
    {
        $alternative = Exercise::create(['gym_id' => $this->gym->id, 'title' => 'Alternative', 'muscle_group' => 'pecho']);
        $otherDay = RoutineDay::create(['routine_id' => $this->routine->id, 'day_number' => 2, 'title' => 'Otro día']);
        RoutineDayExercise::create(['routine_day_id' => $otherDay->id, 'exercise_id' => $this->exercise->id, 'sets' => 3, 'reps' => '10', 'order' => 1]);
        $otherAssignment = Assignment::create(['gym_id' => $this->gym->id, 'client_id' => $this->client->id, 'routine_id' => $this->routine->id, 'status' => 'active']);
        $this->actingAs($this->client);
        Livewire::test('client.workout-logger', ['assignment' => $this->assignment])
            ->call('selectDay', $this->day->id)->call('replaceExercise', $alternative->id)
            ->call('selectDay', $otherDay->id)->assertDontSee('Original del entrenador:');
        Livewire::test('client.workout-logger', ['assignment' => $otherAssignment])
            ->call('selectDay', $this->day->id)->assertDontSee('Original del entrenador:');
    }

    public function test_stale_tab_cannot_save_values_under_an_exercise_changed_in_another_tab(): void
    {
        $alternative = Exercise::create(['gym_id' => $this->gym->id, 'title' => 'Press mancuernas', 'muscle_group' => 'pecho']);
        $this->actingAs($this->client);
        $page = Livewire::test('client.workout-logger', ['assignment' => $this->assignment])
            ->call('selectDay', $this->day->id)->set('inputs.1.weight', 100)->set('inputs.1.reps', 10);
        app(ExerciseSetRecorder::class)->replace($this->assignment, $this->client, $this->routineDayExercise->id, $alternative->id);
        $page->call('logSet', 1)->assertHasErrors('workout_context')->assertNotDispatched('set-logged')
            ->assertSet('inputs.1.weight', '')->assertSet('inputs.1.reps', '')->assertSee('Volvé a ingresar');
        $this->assertDatabaseCount('exercise_logs', 0);
        $page->set('inputs.1.weight', 24)->set('inputs.1.reps', 10)->call('logSet', 1)->assertHasNoErrors();
        $this->assertDatabaseHas('exercise_logs', ['exercise_id' => $alternative->id, 'weight' => 24]);
    }

    public function test_overnight_form_cannot_save_yesterdays_alternative_as_todays_original(): void
    {
        $alternative = Exercise::create(['gym_id' => $this->gym->id, 'title' => 'Press mancuernas', 'muscle_group' => 'pecho']);
        $this->actingAs($this->client);
        $page = Livewire::test('client.workout-logger', ['assignment' => $this->assignment])
            ->call('selectDay', $this->day->id)->call('replaceExercise', $alternative->id)
            ->set('inputs.1.weight', 24)->set('inputs.1.reps', 10);
        $this->travel(1)->days();
        $page->call('logSet', 1)->assertHasErrors('workout_context')->assertNotDispatched('set-logged')
            ->assertSet('inputs.1.weight', '')->assertSet('inputs.1.reps', '');
        $this->assertDatabaseCount('exercise_logs', 0);
    }

    public function test_http_save_requires_matching_exercise_context_after_a_replacement(): void
    {
        $alternative = Exercise::create(['gym_id' => $this->gym->id, 'title' => 'Press mancuernas', 'muscle_group' => 'pecho']);
        app(ExerciseSetRecorder::class)->replace($this->assignment, $this->client, $this->routineDayExercise->id, $alternative->id);
        $payload = ['assignment_id' => $this->assignment->id, 'routine_day_exercise_id' => $this->routineDayExercise->id, 'set_number' => 1, 'weight' => 100, 'reps' => 10];
        $this->actingAs($this->client)->post(route('client.logs.store'), $payload)->assertSessionHasErrors('workout_context');
        $this->assertDatabaseCount('exercise_logs', 0);
        $this->post(route('client.logs.store'), $payload + ['expected_exercise_id' => $alternative->id, 'performed_on' => today()->toDateString()])->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('exercise_logs', ['exercise_id' => $alternative->id]);
    }
}
