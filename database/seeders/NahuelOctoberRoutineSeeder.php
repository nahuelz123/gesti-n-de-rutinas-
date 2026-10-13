<?php

namespace Database\Seeders;

use App\Models\Exercise;
use App\Models\Routine;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NahuelOctoberRoutineSeeder extends Seeder
{
    private const TITLE = 'Hipertrofia — 5 días (octubre 2026)';

    public function run(): void
    {
        $coach = User::query()->where('email', 'nahuelarielz1234@gmail.com')
            ->whereIn('role', ['admin', 'coach', 'super_admin'])->first();
        if (! $coach || ! $coach->gym_id) {
            return;
        }

        DB::transaction(function () use ($coach): void {
            // Conservar las rutinas previas, sus asignaciones y los registros de entrenamiento.
            if (Routine::withTrashed()->where('gym_id', $coach->gym_id)
                ->where('coach_id', $coach->id)->where('title', self::TITLE)->exists()) {
                return;
            }

            $routine = Routine::create([
                'gym_id' => $coach->gym_id,
                'coach_id' => $coach->id,
                'title' => self::TITLE,
                'description' => 'Cinco días de hipertrofia. Compuestos a 1–2 RIR (2–3 min); aislados a 0–1 RIR (1–2 min). Subí peso al completar el rango con buena técnica. Remo T con apoyo de pecho; si la máquina no tiene apoyo, usá remo con barra o Hammer. No se asigna automáticamente.',
            ]);

            foreach (self::days() as $dayNumber => [$title, $exercises]) {
                $day = $routine->days()->create(['day_number' => $dayNumber + 1, 'title' => $title]);
                foreach ($exercises as $index => [$name, $group, $sets, $reps, $compound, $note]) {
                    $exercise = Exercise::query()->where('title', $name)
                        ->where(function ($query) use ($coach): void {
                            $query->where('is_global', true)->orWhere('gym_id', $coach->gym_id);
                        })
                        ->orderByRaw('CASE WHEN gym_id = ? THEN 0 ELSE 1 END', [$coach->gym_id])
                        ->first();

                    if (! $exercise) {
                        $exercise = Exercise::firstOrCreate(
                            ['gym_id' => $coach->gym_id, 'title' => $name],
                            ['muscle_group' => $group, 'is_global' => false, 'created_by_id' => $coach->id],
                        );
                    }
                    $day->exercises()->create([
                        'exercise_id' => $exercise->id,
                        'sets' => $sets, 'reps' => $reps,
                        'rest' => $compound ? '2-3 min' : '1-2 min',
                        'notes' => ($compound ? '1–2 RIR.' : '0–1 RIR.').($note ? ' '.$note : ''),
                        'order' => $index + 1,
                    ]);
                }
            }
        });
    }

    /** @return array<int, array{string, array<int, array{string, string, int, string, bool, string}>}> */
    private static function days(): array
    {
        return [
            ['Lunes — Pierna 1', [
                ['Sentadilla', 'piernas', 4, '6-8', true, 'Profunda con rango cómodo.'],
                ['Peso muerto rumano', 'piernas', 3, '8-10', true, 'Empezá con 2 series moderadas y subí a 3 si recuperás bien.'],
                ['Hip thrust', 'gluteos', 2, '8-12', true, ''],
                ['Prensa unilateral', 'piernas', 3, '10-12 por pierna', true, ''],
                ['Extensión de cuádriceps', 'piernas', 2, '10-15', false, 'Sillón de cuádriceps.'],
                ['Camilla de isquios', 'piernas', 2, '10-15', false, 'Curl femoral acostado.'],
                ['Gemelos', 'piernas', 3, '12-15', false, ''],
            ]],
            ['Martes — Pecho / Hombro / Tríceps', [
                ['Press pecho plano en máquina', 'pecho', 4, '6-10', true, ''],
                ['Press banca inclinado con mancuernas', 'pecho', 3, '8-10', true, 'Alternativa: press inclinado con barra.'],
                ['Cruces en polea', 'pecho', 3, '12-15', false, ''],
                ['Press militar en máquina', 'hombros', 3, '8-10', true, ''],
                ['Elevaciones laterales', 'hombros', 3, '12-20', false, 'Vuelos laterales.'],
                ['Press cerrado con barra', 'triceps', 3, '8-10', true, ''],
                ['Tríceps katana bilateral', 'triceps', 2, '10-15', false, 'Dos brazos por encima de la cabeza.'],
                ['Tríceps soga', 'triceps', 2, '10-15', false, 'Extensión en polea con soga.'],
            ]],
            ['Miércoles — Espalda / Bíceps', [
                ['Dominadas', 'espalda', 4, '6-10', true, ''],
                ['Remo T con apoyo de pecho', 'espalda', 4, '6-10', true, 'Si no hay apoyo, usá remo con barra o Remo Hammer.'],
                ['Pullover en polea', 'espalda', 3, '10-15', false, ''],
                ['Jalón al pecho', 'espalda', 3, '8-12', true, ''],
                ['Posterior peck deck', 'hombros', 3, '12-20', false, ''],
                ['Curl Scott', 'biceps', 3, '8-12', false, 'Barra W si resulta cómoda.'],
                ['Curl en polea', 'biceps', 3, '10-15', false, 'Con soga.'],
                ['Antebrazo en polea', 'antebrazo', 2, '12-20', false, 'Opcional.'],
            ]],
            ['Jueves — Pierna 2', [
                ['Prensa', 'piernas', 4, '8-12', true, 'Bilateral.'],
                ['Camilla de isquios', 'piernas', 3, '10-12', false, ''],
                ['Extensión de cuádriceps', 'piernas', 3, '12-15', false, 'Sillón.'],
                ['Prensa unilateral', 'piernas', 2, '10-12 por pierna', true, ''],
                ['Sillón invertido unilateral', 'piernas', 2, '10-15', false, 'Isquios.'],
                ['Abductores', 'gluteos', 2, '15-20', false, ''],
                ['Gemelos', 'piernas', 3, '12-20', false, ''],
            ]],
            ['Viernes — Torso', [
                ['Press pecho plano en máquina', 'pecho', 4, '6-10', true, ''],
                ['Jalón al pecho', 'espalda', 4, '8-12', true, ''],
                ['Remo en máquina', 'espalda', 4, '6-10', true, 'Remo Hammer si está disponible.'],
                ['Cruces en polea', 'pecho', 3, '12-15', false, ''],
                ['Press cerrado con barra', 'triceps', 2, '8-12', true, 'Reemplaza KAS/JM press.'],
                ['Elevaciones laterales', 'hombros', 3, '12-20', false, ''],
                ['Curl con mancuernas', 'biceps', 3, '8-12', false, 'Alternado.'],
                ['Crunch', 'abdomen', 3, '10-20', false, ''],
            ]],
        ];
    }
}
