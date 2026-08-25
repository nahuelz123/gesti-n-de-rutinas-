<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\Gym;
use App\Models\Recipe;
use App\Models\User;
use App\Services\CoachAiTools;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CatalogSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $coachA;
    private User $coachB;
    private Gym $gymA;
    private Gym $gymB;
    private Exercise $globalEx;
    private Exercise $localExA;
    private Exercise $localExB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gymA = Gym::create(['name' => 'Gym A']);
        $this->gymB = Gym::create(['name' => 'Gym B']);

        $this->coachA = User::factory()->create(['role' => 'coach', 'gym_id' => $this->gymA->id]);
        $this->coachB = User::factory()->create(['role' => 'coach', 'gym_id' => $this->gymB->id]);

        $this->globalEx = Exercise::create(['title' => 'Global Ex', 'muscle_group' => 'pecho', 'is_global' => true]);
        $this->localExA = Exercise::create(['title' => 'Local Ex A', 'muscle_group' => 'pecho', 'is_global' => false, 'gym_id' => $this->gymA->id]);
        $this->localExB = Exercise::create(['title' => 'Local Ex B', 'muscle_group' => 'pecho', 'is_global' => false, 'gym_id' => $this->gymB->id]);
    }

    #[Test]
    public function coach_can_view_global_exercise()
    {
        $this->actingAs($this->coachA);
        $this->assertTrue($this->coachA->can('view', $this->globalEx));
    }

    #[Test]
    public function coach_can_view_own_private_exercise()
    {
        $this->actingAs($this->coachA);
        $this->assertTrue($this->coachA->can('view', $this->localExA));
    }

    #[Test]
    public function coach_cannot_view_other_gym_private_exercise()
    {
        $this->actingAs($this->coachA);
        $this->assertFalse($this->coachA->can('view', $this->localExB));
    }

    #[Test]
    public function coach_gym_id_is_forced_on_creation_ignoring_input()
    {
        $this->actingAs($this->coachA);
        $ex = new Exercise();
        $ex->gym_id = $this->gymB->id;
        $ex->title = 'Test';
        $ex->muscle_group = 'pecho';
        $ex->is_global = false;
        $ex->save();

        $this->assertEquals($this->gymA->id, $ex->gym_id);
    }

    #[Test]
    public function coach_cannot_create_global_exercise()
    {
        $this->actingAs($this->coachA);
        $ex = new Exercise();
        $ex->gym_id = $this->gymA->id;
        $ex->title = 'Test';
        $ex->muscle_group = 'pecho';
        $ex->is_global = true;
        $ex->save();

        $this->assertFalse($ex->is_global);
    }

    #[Test]
    public function coach_cannot_edit_global_exercise()
    {
        $this->actingAs($this->coachA);
        $this->assertFalse($this->coachA->can('update', $this->globalEx));
    }

    #[Test]
    public function coach_cannot_delete_global_exercise()
    {
        $this->actingAs($this->coachA);
        $this->assertFalse($this->coachA->can('delete', $this->globalEx));
    }

    #[Test]
    public function ai_cannot_use_recipe_from_another_gym()
    {
        $recipeB = Recipe::create(['title' => 'Recipe B', 'is_global' => false, 'gym_id' => $this->gymB->id, 'created_by_id' => $this->coachB->id]);
        
        $reflection = new \ReflectionClass(CoachAiTools::class);
        $method = $reflection->getMethod('resolveRecipeRobust');
        $method->setAccessible(true);
        
        $result = $method->invoke(null, 'Recipe B', $this->coachA);
        $this->assertEquals('missing', $result['status']);
    }

    #[Test]
    public function ai_prioritizes_local_over_global()
    {
        Exercise::create(['title' => 'Sentadilla', 'muscle_group' => 'piernas', 'is_global' => true]);
        Exercise::create(['title' => 'Sentadilla', 'muscle_group' => 'piernas', 'is_global' => false, 'gym_id' => $this->gymA->id]);

        $reflection = new \ReflectionClass(CoachAiTools::class);
        $method = $reflection->getMethod('resolveExerciseRobust');
        $method->setAccessible(true);
        
        $result = $method->invoke(null, 'Sentadilla', $this->coachA);
        $this->assertEquals('ok', $result['status']);
        $this->assertFalse($result['exercise']->is_global);
        $this->assertEquals($this->gymA->id, $result['exercise']->gym_id);
    }

    #[Test]
    public function ai_returns_ambiguous_for_multiple_matches()
    {
        Exercise::create(['title' => 'Press banca plano', 'muscle_group' => 'pecho', 'is_global' => true]);
        Exercise::create(['title' => 'Press banca inclinado', 'muscle_group' => 'pecho', 'is_global' => true]);

        $reflection = new \ReflectionClass(CoachAiTools::class);
        $method = $reflection->getMethod('resolveExerciseRobust');
        $method->setAccessible(true);
        
        $result = $method->invoke(null, 'Press banca', $this->coachA);
        $this->assertEquals('ambiguous', $result['status']);
    }
}
