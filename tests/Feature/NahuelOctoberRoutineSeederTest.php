<?php

namespace Tests\Feature;

use App\Models\Gym;
use App\Models\Routine;
use App\Models\User;
use Database\Seeders\NahuelOctoberRoutineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NahuelOctoberRoutineSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_revised_five_day_routine_is_added_once_to_the_coachs_repertoire(): void
    {
        $gym = Gym::create(['name' => 'Gym Nahuel']);
        $coach = User::factory()->create([
            'email' => 'nahuelarielz1234@gmail.com', 'role' => 'coach', 'gym_id' => $gym->id,
        ]);

        $this->seed(NahuelOctoberRoutineSeeder::class);
        $this->seed(NahuelOctoberRoutineSeeder::class);

        $routine = Routine::query()->where('coach_id', $coach->id)
            ->where('title', 'Hipertrofia — 5 días (octubre 2026)')->firstOrFail();
        $days = $routine->days()->orderBy('day_number')->with('exercises.exercise')->get();
        $this->assertSame([7, 8, 8, 7, 8], $days->map(fn ($day) => $day->exercises->count())->all());
        $this->assertSame(
            ['Sentadilla', 'Peso muerto rumano', 'Hip thrust', 'Prensa unilateral',
                'Extensión de cuádriceps', 'Camilla de isquios', 'Gemelos'],
            $days[0]->exercises->map(fn ($item) => $item->exercise->title)->all()
        );
        $this->assertSame('2', (string) $days[0]->exercises[2]->sets);
        $this->assertSame(
            ['Press pecho plano en máquina', 'Press banca inclinado con mancuernas',
                'Cruces en polea', 'Press militar en máquina', 'Elevaciones laterales',
                'Press cerrado con barra', 'Tríceps katana bilateral', 'Tríceps soga'],
            $days[1]->exercises->map(fn ($item) => $item->exercise->title)->all()
        );
        $this->assertSame('Remo T con apoyo de pecho', $days[2]->exercises[1]->exercise->title);
        $this->assertSame('Prensa', $days[3]->exercises[0]->exercise->title);
        $this->assertSame('Press pecho plano en máquina', $days[4]->exercises[0]->exercise->title);
        $this->assertSame(1, Routine::query()->where('title', $routine->title)->count());
        $this->assertDatabaseCount('assignments', 0);
    }
}
