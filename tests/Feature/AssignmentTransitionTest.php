<?php

namespace Tests\Feature;

use App\Filament\Resources\Assignments\Pages\CreateAssignment;
use App\Models\Assignment;
use App\Models\Gym;
use App\Models\Routine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AssignmentTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_an_active_assignment_completes_the_previous_one(): void
    {
        $gym = Gym::create(['name' => 'Gym']);
        $admin = User::factory()->create(['role' => 'admin', 'gym_id' => $gym->id]);
        $client = User::factory()->create(['role' => 'client', 'gym_id' => $gym->id]);
        $oldRoutine = Routine::create(['gym_id' => $gym->id, 'coach_id' => $admin->id, 'title' => 'Anterior']);
        $newRoutine = Routine::create(['gym_id' => $gym->id, 'coach_id' => $admin->id, 'title' => 'Nueva']);

        $this->actingAs($admin);

        $previous = Assignment::create([
            'gym_id' => $gym->id,
            'client_id' => $client->id,
            'routine_id' => $oldRoutine->id,
            'assigned_by_id' => $admin->id,
            'assigned_at' => now(),
            'status' => 'active',
        ]);

        Livewire::test(CreateAssignment::class)
            ->fillForm([
                'client_id' => $client->id,
                'routine_id' => $newRoutine->id,
                'status' => 'active',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('completed', $previous->fresh()->status);
        $this->assertNotNull($previous->fresh()->end_date);
        $this->assertDatabaseHas('assignments', [
            'client_id' => $client->id,
            'routine_id' => $newRoutine->id,
            'status' => 'active',
            'end_date' => null,
        ]);
        $this->assertSame(1, Assignment::where('client_id', $client->id)->where('status', 'active')->count());
    }

    public function test_a_rejected_new_assignment_keeps_the_previous_one_active(): void
    {
        $gym = Gym::create(['name' => 'Gym']);
        $otherGym = Gym::create(['name' => 'Otro gym']);
        $admin = User::factory()->create(['role' => 'admin', 'gym_id' => $gym->id]);
        $otherCoach = User::factory()->create(['role' => 'coach', 'gym_id' => $otherGym->id]);
        $client = User::factory()->create(['role' => 'client', 'gym_id' => $gym->id]);
        $routine = Routine::create(['gym_id' => $gym->id, 'coach_id' => $admin->id, 'title' => 'Rutina']);

        $this->actingAs($admin);

        $previous = Assignment::create([
            'gym_id' => $gym->id,
            'client_id' => $client->id,
            'routine_id' => $routine->id,
            'assigned_by_id' => $admin->id,
            'assigned_at' => now(),
            'status' => 'active',
        ]);

        try {
            Livewire::test(CreateAssignment::class)
                ->fillForm([
                    'client_id' => $client->id,
                    'routine_id' => $routine->id,
                    'assigned_by_id' => $otherCoach->id,
                    'status' => 'active',
                ])
                ->call('create');

            $this->fail('La asignación con otro profesor debió ser rechazada.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('Assigned_by inválido', $error->getMessage());
        }

        $this->assertSame('active', $previous->fresh()->status);
        $this->assertNull($previous->fresh()->end_date);
        $this->assertSame(1, Assignment::where('client_id', $client->id)->count());
    }
}
