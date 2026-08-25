<?php

namespace Tests\Feature;

use App\Filament\Resources\DietPlans\DietPlanResource;
use App\Models\Recipe;
use App\Models\Gym;
use App\Models\DietPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class DietBuilderTest extends TestCase
{
    use RefreshDatabase;

    private User $coachA;
    private User $coachB;
    private Gym $gymA;
    private Gym $gymB;
    private Recipe $globalRecipe;
    private Recipe $localRecipeA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gymA = Gym::create(['name' => 'Gym A']);
        $this->gymB = Gym::create(['name' => 'Gym B']);

        $this->coachA = User::factory()->create(['role' => 'coach', 'gym_id' => $this->gymA->id]);
        $this->coachB = User::factory()->create(['role' => 'coach', 'gym_id' => $this->gymB->id]);

        $this->globalRecipe = Recipe::create(['title' => 'Global Recipe', 'is_global' => true]);
        $this->localRecipeA = Recipe::create(['title' => 'Local Recipe A', 'is_global' => false, 'gym_id' => $this->gymA->id]);
    }

    #[Test]
    public function coach_can_render_create_diet_plan_page()
    {
        $this->actingAs($this->coachA);
        
        $this->get(DietPlanResource::getUrl('create'))
            ->assertSuccessful();
    }

    #[Test]
    public function coach_can_create_diet_plan_with_nested_days_and_recipes()
    {
        $this->actingAs($this->coachA);

        Livewire::test(\App\Filament\Resources\DietPlans\Pages\CreateDietPlan::class)
            ->fillForm([
                'title' => 'Diet Test',
                'goal' => 'perdida_peso',
                'target_calories' => 2000,
                'description' => 'Test Desc',
                'days' => [
                    [
                        'day_of_week' => 'lunes',
                        'notes' => 'Notas lunes',
                        'recipes' => [
                            [
                                'recipe_id' => $this->globalRecipe->id,
                                'meal_type' => 'desayuno',
                                'servings' => 1.5,
                                'order' => 1,
                            ],
                            [
                                'recipe_id' => $this->localRecipeA->id,
                                'meal_type' => 'almuerzo',
                                'servings' => 2,
                                'order' => 2,
                            ]
                        ]
                    ]
                ]
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('diet_plans', [
            'title' => 'Diet Test',
            'gym_id' => $this->gymA->id,
            'goal' => 'perdida_peso',
            'target_calories' => 2000,
        ]);

        $dietPlan = DietPlan::first();

        $this->assertDatabaseHas('diet_plan_days', [
            'diet_plan_id' => $dietPlan->id,
            'day_of_week' => 'lunes',
        ]);

        $this->assertDatabaseHas('diet_plan_day_recipes', [
            'recipe_id' => $this->globalRecipe->id,
            'meal_type' => 'desayuno',
            'servings' => 1.5,
        ]);
        
        $this->assertDatabaseHas('diet_plan_day_recipes', [
            'recipe_id' => $this->localRecipeA->id,
            'meal_type' => 'almuerzo',
            'servings' => 2,
        ]);
    }
}
