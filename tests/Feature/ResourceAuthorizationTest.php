<?php

namespace Tests\Feature;

use App\Filament\Resources\Assignments\AssignmentResource;
use App\Filament\Resources\Routines\RoutineResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Assignment;
use App\Models\Gym;
use App\Models\Routine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_coach_cannot_edit_or_delete_assignments(): void
    {
        [$assignment, $coach] = $this->createAssignmentFixture();
        $this->actingAs($coach);

        $this->assertFalse(AssignmentResource::canEdit($assignment));
        $this->assertFalse(AssignmentResource::canDelete($assignment));
    }

    public function test_admin_can_manage_own_gym_assignment_but_not_a_foreign_one(): void
    {
        [$assignment] = $this->createAssignmentFixture();
        $otherGym = Gym::create(['name' => 'Other gym']);
        $admin = User::factory()->create(['role' => 'admin', 'gym_id' => $assignment->gym_id]);
        $otherAdmin = User::factory()->create(['role' => 'admin', 'gym_id' => $otherGym->id]);

        $this->actingAs($admin);
        $this->assertTrue(AssignmentResource::canEdit($assignment));
        $this->assertTrue(AssignmentResource::canDelete($assignment));

        $this->actingAs($otherAdmin);
        $this->assertFalse(AssignmentResource::canEdit($assignment));
        $this->assertFalse(AssignmentResource::canDelete($assignment));
    }

    public function test_super_admin_can_manage_assignments_across_gyms(): void
    {
        [$assignment] = $this->createAssignmentFixture();
        $otherGym = Gym::create(['name' => 'Other gym']);
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'gym_id' => $otherGym->id]);
        $this->actingAs($superAdmin);

        $this->assertTrue(AssignmentResource::canEdit($assignment));
        $this->assertTrue(AssignmentResource::canDelete($assignment));
    }

    public function test_admin_cannot_modify_super_admin_or_users_outside_their_gym(): void
    {
        $gym = Gym::create(['name' => 'Managed gym']);
        $otherGym = Gym::create(['name' => 'Other gym']);
        $admin = User::factory()->create(['role' => 'admin', 'gym_id' => $gym->id]);
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'gym_id' => $gym->id]);
        $ownClient = User::factory()->create(['role' => 'client', 'gym_id' => $gym->id]);
        $otherClient = User::factory()->create(['role' => 'client', 'gym_id' => $otherGym->id]);

        $this->actingAs($admin);
        $this->assertTrue(UserResource::canEdit($ownClient));
        $this->assertFalse(UserResource::canEdit($superAdmin));
        $this->assertFalse(UserResource::canEdit($otherClient));
        $this->assertFalse($admin->can('delete', $admin));
    }

    public function test_super_admin_can_edit_users_from_any_gym(): void
    {
        $first = Gym::create(['name' => 'First gym']);
        $second = Gym::create(['name' => 'Second gym']);
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'gym_id' => $first->id]);
        $client = User::factory()->create(['role' => 'client', 'gym_id' => $second->id]);

        $this->actingAs($superAdmin);

        $this->assertTrue(UserResource::canEdit($client));
    }

    public function test_super_admin_can_edit_and_delete_routines_in_any_gym(): void
    {
        [$assignment] = $this->createAssignmentFixture();
        $otherGym = Gym::create(['name' => 'Other gym']);
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'gym_id' => $otherGym->id]);
        $this->actingAs($superAdmin);

        $this->assertTrue(RoutineResource::canEdit($assignment->routine));
        $this->assertTrue(RoutineResource::canDelete($assignment->routine));
    }

    public function test_client_cannot_use_the_routine_admin_policy_even_in_their_own_gym(): void
    {
        [$assignment] = $this->createAssignmentFixture();
        $client = $assignment->client;
        $this->actingAs($client);

        $this->assertFalse($client->can('view', $assignment->routine));
        $this->assertFalse($client->can('update', $assignment->routine));
    }

    public function test_coach_cannot_delete_routine(): void
    {
        [$assignment, $coach] = $this->createAssignmentFixture();
        $this->actingAs($coach);

        $this->assertTrue(RoutineResource::canEdit($assignment->routine));
        $this->assertFalse(RoutineResource::canDelete($assignment->routine));
    }

    private function createAssignmentFixture(): array
    {
        $gym = Gym::create(['name' => 'First gym']);
        $coach = User::factory()->create(['role' => 'coach', 'gym_id' => $gym->id]);
        $client = User::factory()->create(['role' => 'client', 'gym_id' => $gym->id]);
        $routine = Routine::create([
            'gym_id' => $gym->id,
            'coach_id' => $coach->id,
            'title' => 'Training plan',
        ]);
        $assignment = Assignment::create([
            'gym_id' => $gym->id,
            'routine_id' => $routine->id,
            'client_id' => $client->id,
            'assigned_by_id' => $coach->id,
            'status' => 'active',
            'assigned_at' => now(),
        ]);

        return [$assignment, $coach];
    }
}
