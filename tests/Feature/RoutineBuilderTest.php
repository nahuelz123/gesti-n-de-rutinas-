<?php

namespace Tests\Feature;

use App\Filament\Resources\Routines\RoutineResource;
use App\Models\Exercise;
use App\Models\Gym;
use App\Models\Routine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RoutineBuilderTest extends TestCase
{
    use RefreshDatabase;

    private User $coachA;
    private User $coachB;
    private Gym $gymA;
    private Gym $gymB;
    private Exercise $globalEx;
    private Exercise $localExA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gymA = Gym::create(['name' => 'Gym A']);
        $this->gymB = Gym::create(['name' => 'Gym B']);

        $this->coachA = User::factory()->create(['role' => 'coach', 'gym_id' => $this->gymA->id]);
        $this->coachB = User::factory()->create(['role' => 'coach', 'gym_id' => $this->gymB->id]);

        $this->globalEx = Exercise::create(['title' => 'Global Ex', 'muscle_group' => 'pecho', 'is_global' => true]);
        $this->localExA = Exercise::create(['title' => 'Local Ex A', 'muscle_group' => 'pecho', 'is_global' => false, 'gym_id' => $this->gymA->id]);
    }

    #[Test]
    public function coach_can_render_create_routine_page()
    {
        $this->actingAs($this->coachA);
        
        $this->get(RoutineResource::getUrl('create'))
            ->assertSuccessful();
    }

    #[Test]
    public function coach_can_create_routine_with_nested_days_and_exercises()
    {
        $this->actingAs($this->coachA);

        Livewire::test(\App\Filament\Resources\Routines\Pages\CreateRoutine::class)
            ->fillForm([
                'title' => 'Rutina Test',
                'description' => 'Test Desc',
                'days' => [
                    [
                        'title' => 'Dia 1',
                        'day_number' => 1,
                        'exercises' => [
                            [
                                'exercise_id' => $this->globalEx->id,
                                'sets' => 4,
                                'reps' => '10',
                                'rest' => '60s',
                                'order' => 1,
                            ],
                            [
                                'exercise_id' => $this->localExA->id,
                                'sets' => 3,
                                'reps' => '12',
                                'rest' => '90s',
                                'order' => 2,
                            ]
                        ]
                    ]
                ]
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('routines', [
            'title' => 'Rutina Test',
            'gym_id' => $this->gymA->id,
        ]);

        $routine = Routine::first();

        $this->assertDatabaseHas('routine_days', [
            'routine_id' => $routine->id,
            'title' => 'Dia 1',
        ]);

        $this->assertDatabaseHas('routine_day_exercises', [
            'exercise_id' => $this->globalEx->id,
            'sets' => 4,
            'reps' => '10',
        ]);
        
        $this->assertDatabaseHas('routine_day_exercises', [
            'exercise_id' => $this->localExA->id,
            'sets' => 3,
            'reps' => '12',
        ]);
    }
}
