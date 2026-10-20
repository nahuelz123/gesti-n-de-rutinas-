<?php

namespace Tests\Feature;

use App\Filament\Resources\Exercises\Pages\ListExercises;
use App\Filament\Resources\FoodItems\FoodItemResource;
use App\Filament\Resources\Routines\Pages\EditRoutine;
use App\Models\Assignment;
use App\Models\Exercise;
use App\Models\ExerciseLog;
use App\Models\FoodItem;
use App\Models\Gym;
use App\Models\Routine;
use App\Models\RoutineDay;
use App\Models\RoutineDayExercise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class ReleaseSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_read_but_cannot_edit_or_delete_global_foods(): void
    {
        $gym = Gym::create(['name' => 'Gym']);
        $otherGym = Gym::create(['name' => 'Otro']);
        $coach = User::factory()->create(['role' => 'coach', 'gym_id' => $gym->id]);
        $otherCoach = User::factory()->create(['role' => 'coach', 'gym_id' => $otherGym->id]);
        $global = FoodItem::create(['name' => 'Global', 'calories_per_100g' => 100, 'is_global' => true]);
        $own = FoodItem::create(['name' => 'Propio', 'calories_per_100g' => 100, 'created_by_id' => $coach->id]);
        $foreign = FoodItem::create(['name' => 'Ajeno', 'calories_per_100g' => 100, 'created_by_id' => $otherCoach->id]);
        $super = User::factory()->create(['role' => 'super_admin', 'gym_id' => $gym->id]);

        $this->actingAs($coach);
        $this->assertFalse(FoodItemResource::canEdit($global));
        $this->assertFalse(FoodItemResource::canDelete($global));
        $this->assertFalse(FoodItemResource::canEdit($foreign));
        $this->assertTrue(FoodItemResource::canEdit($own));
        $this->assertTrue(FoodItemResource::canDelete($own));

        $this->actingAs($super);
        $this->assertTrue(FoodItemResource::canEdit($global));
        $this->assertTrue(FoodItemResource::canDelete($global));
    }

    public function test_bulk_deletion_preserves_global_exercises_and_only_deletes_authorized_records(): void
    {
        $gym = Gym::create(['name' => 'Gym']);
        $coach = User::factory()->create(['role' => 'coach', 'gym_id' => $gym->id]);
        $global = Exercise::create(['title' => 'Global', 'muscle_group' => 'pecho', 'is_global' => true]);
        $own = Exercise::create(['title' => 'Propio', 'muscle_group' => 'pecho', 'gym_id' => $gym->id]);

        $this->actingAs($coach);
        Livewire::test(ListExercises::class)->callTableBulkAction('delete', [$global, $own]);

        $this->assertNotSoftDeleted($global);
        $this->assertSoftDeleted($own);
    }

    public function test_client_cannot_read_another_gyms_private_exercise_progress_page(): void
    {
        [$client] = $this->workout();
        $otherGym = Gym::create(['name' => 'Otro']);
        $foreign = Exercise::create(['title' => 'Privado ajeno', 'muscle_group' => 'pecho', 'gym_id' => $otherGym->id]);
        $this->actingAs($client)->get(route('client.progress.exercise', $foreign))->assertNotFound();
    }

    public function test_deleting_an_exercise_from_catalog_does_not_break_existing_workouts(): void
    {
        [$client, $assignment, $row] = $this->workout();
        $row->exercise->delete();

        $this->actingAs($client);
        $this->get(route('client.routines.show', $assignment))->assertOk()->assertSee('Press de prueba');
        Livewire::test('client.workout-logger', ['assignment' => $assignment])
            ->call('selectDay', $row->routine_day_id)
            ->assertSee('Press de prueba');
    }

    public function test_removing_a_recorded_exercise_cannot_erase_client_history(): void
    {
        [, , $row, $log] = $this->workout(withLog: true);
        $this->assertHistoryEditRejected(fn () => $row->delete());
        $this->assertDatabaseHas('exercise_logs', ['id' => $log->id, 'weight' => 60]);
        $this->assertModelExists($row);
    }

    public function test_removing_a_recorded_day_cannot_erase_client_history(): void
    {
        [, , $row, $log] = $this->workout(withLog: true);
        $this->assertHistoryEditRejected(fn () => $row->routineDay->delete());
        $this->assertDatabaseHas('exercise_logs', ['id' => $log->id]);
        $this->assertModelExists($row);
    }

    public function test_replacing_a_recorded_exercise_cannot_transfer_old_sets_to_another_exercise(): void
    {
        [, , $row, $log] = $this->workout(withLog: true);
        $originalId = $row->exercise_id;
        $replacement = Exercise::create(['title' => 'Otra actividad', 'muscle_group' => 'espalda', 'is_global' => true]);
        $this->assertHistoryEditRejected(fn () => $row->update(['exercise_id' => $replacement->id]));
        $this->assertSame($originalId, $row->fresh()->exercise_id);
        $this->assertDatabaseHas('exercise_logs', ['id' => $log->id]);
    }

    public function test_moving_a_recorded_day_cannot_remove_it_from_the_original_routine_history(): void
    {
        [, $assignment, $row, $log] = $this->workout(withLog: true);
        $other = Routine::create(['gym_id' => $assignment->gym_id, 'coach_id' => $assignment->assigned_by_id, 'title' => 'Otra versión']);
        $this->assertHistoryEditRejected(fn () => $row->routineDay->update(['routine_id' => $other->id]));
        $this->assertSame($assignment->routine_id, $row->routineDay->fresh()->routine_id);
        $this->assertDatabaseHas('exercise_logs', ['id' => $log->id]);
    }

    public function test_routine_builder_rolls_back_other_edits_when_removing_a_recorded_day(): void
    {
        [, $assignment, $row, $log] = $this->workout(withLog: true);
        $this->actingAs($assignment->assignedBy);
        Livewire::test(EditRoutine::class, ['record' => $assignment->routine_id])
            ->fillForm(['title' => 'No debe persistir', 'days' => []])
            ->call('save')
            ->assertHasFormErrors(['days']);
        $this->assertSame('Rutina', $assignment->routine->fresh()->title);
        $this->assertDatabaseHas('exercise_logs', ['id' => $log->id]);
        $this->assertModelExists($row);
    }

    public function test_unused_routine_items_can_still_be_removed(): void
    {
        [, , $row] = $this->workout();
        $row->delete();
        $this->assertModelMissing($row);
        $row->routineDay->delete();
        $this->assertModelMissing($row->routineDay);
    }

    private function assertHistoryEditRejected(callable $edit): void
    {
        try {
            $edit();
            $this->fail('Un cambio destructivo no debe borrar ni reasignar series registradas.');
        } catch (ValidationException $error) {
            $this->assertNotEmpty($error->errors());
        }
    }

    private function workout(bool $withLog = false): array
    {
        $gym = Gym::create(['name' => 'Gym']);
        $coach = User::factory()->create(['role' => 'coach', 'gym_id' => $gym->id]);
        $client = User::factory()->create(['role' => 'client', 'gym_id' => $gym->id]);
        $routine = Routine::create(['gym_id' => $gym->id, 'coach_id' => $coach->id, 'title' => 'Rutina']);
        $exercise = Exercise::create(['title' => 'Press de prueba', 'muscle_group' => 'pecho', 'gym_id' => $gym->id]);
        $day = RoutineDay::create(['routine_id' => $routine->id, 'title' => 'Pecho', 'day_number' => 1]);
        $row = RoutineDayExercise::create(['routine_day_id' => $day->id, 'exercise_id' => $exercise->id, 'sets' => 3, 'reps' => '8-10', 'order' => 1]);
        $assignment = Assignment::create(['gym_id' => $gym->id, 'routine_id' => $routine->id, 'client_id' => $client->id, 'assigned_by_id' => $coach->id, 'status' => 'active', 'assigned_at' => now()]);
        $log = $withLog ? ExerciseLog::create(['assignment_id' => $assignment->id, 'routine_day_exercise_id' => $row->id, 'set_number' => 1, 'weight' => 60, 'reps' => 10, 'logged_at' => now()]) : null;

        return [$client, $assignment, $row, $log];
    }
}
