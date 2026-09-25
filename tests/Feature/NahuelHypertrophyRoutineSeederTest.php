<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\Gym;
use App\Models\Routine;
use App\Models\User;
use Database\Seeders\NahuelHypertrophyRoutineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NahuelHypertrophyRoutineSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_five_day_routine_once_for_the_correct_coach(): void
    {
        $gym = Gym::create(['name' => 'Visión Nahuel']);
        $otherGym = Gym::create(['name' => 'Otro gimnasio']);
        $coach = User::factory()->create([
            'email' => 'nahuelarielz1234@gmail.com',
            'role' => 'admin',
            'gym_id' => $gym->id,
        ]);
        $otherCoach = User::factory()->create(['role' => 'coach', 'gym_id' => $otherGym->id]);

        // Nunca reutilizar un ejercicio privado de otro gimnasio.
        $privateExercise = Exercise::create([
            'title' => 'Press banca con barra',
            'muscle_group' => 'pecho',
            'gym_id' => $otherGym->id,
            'created_by_id' => $otherCoach->id,
            'is_global' => false,
        ]);

        $this->seed(NahuelHypertrophyRoutineSeeder::class);
        $this->seed(NahuelHypertrophyRoutineSeeder::class);

        $routine = Routine::where('coach_id', $coach->id)->where('title', 'Hipertrofia — 5 días')->firstOrFail();
        $days = $routine->days()->orderBy('day_number')->with('exercises.exercise')->get();

        $this->assertSame($gym->id, $routine->gym_id);
        $this->assertSame([7, 8, 7, 9, 8], $days->map(fn ($day) => $day->exercises->count())->all());
        $this->assertSame('Press banca con barra', $days[0]->exercises[0]->exercise->title);
        $this->assertSame('6-8', $days[0]->exercises[0]->reps);
        $this->assertSame('10-12 por pierna', $days[2]->exercises[3]->reps);
        $this->assertStringContainsString('cuarta', $days[4]->exercises[7]->notes);
        $this->assertNotEquals($privateExercise->id, $days[0]->exercises[0]->exercise_id);
        $this->assertSame(1, Routine::where('title', 'Hipertrofia — 5 días')->count());
        $this->assertDatabaseCount('assignments', 0);
    }
}
