<?php

namespace Database\Seeders;

use App\Models\Exercise;
use App\Models\Routine;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NahuelHypertrophyRoutineSeeder extends Seeder
{
    private const TITLE = 'Hipertrofia — 5 días';

    public function run(): void
    {
        $coach = User::where('email', 'nahuelarielz1234@gmail.com')
            ->whereIn('role', ['admin', 'coach', 'super_admin'])
            ->first();

        // En una base nueva (como CI), la cuenta real todavía no existe.
        if (! $coach || ! $coach->gym_id) {
            return;
        }

        DB::transaction(function () use ($coach): void {
            $existing = Routine::withTrashed()
                ->where('coach_id', $coach->id)
                ->where('gym_id', $coach->gym_id)
                ->where('title', self::TITLE)
                ->first();

            // No sobrescribir una rutina que el profesor ya haya editado.
            if ($existing) {
                return;
            }

            $routine = Routine::create([
                'gym_id' => $coach->gym_id,
                'coach_id' => $coach->id,
                'title' => self::TITLE,
                'description' => 'Objetivo: hipertrofia, técnica, rango completo, recuperación y volumen efectivo. Compuestos: 1–2 RIR y descansos de 2–3 min. Aislados: 0–1 RIR y descansos de 1–2 min. Fallo real solo puntualmente en la última serie de aislados. Progresión doble: alcanzar el máximo de repeticiones en todas las series con buena técnica antes de subir la carga (por ejemplo, 8-8-8-8 en 4x6-8). Tempo y progresión de 8 semanas pendientes de definir.',
            ]);

            foreach (self::days() as $dayNumber => [$title, $exercises]) {
                $day = $routine->days()->create([
                    'day_number' => $dayNumber + 1,
                    'title' => $title,
                ]);

                foreach ($exercises as $index => [$name, $group, $sets, $reps, $compound, $extra]) {
                    $exercise = Exercise::where('title', $name)
                        ->where(function ($query) use ($coach): void {
                            $query->whereNull('gym_id')->orWhere('gym_id', $coach->gym_id);
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
                        'sets' => $sets,
                        'reps' => $reps,
                        'rest' => $compound ? '2-3 min' : '1-2 min',
                        'notes' => ($compound ? '1–2 RIR.' : '0–1 RIR.') . ($extra ? ' '.$extra : ''),
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
            ['Día 1 — Pecho + hombro + tríceps', [
                ['Press banca con barra', 'pecho', 4, '6-8', true, 'Press banca plano.'],
                ['Press banca inclinado con mancuernas', 'pecho', 3, '8-10', true, ''],
                ['Cruces en polea', 'pecho', 3, '12-15', false, ''],
                ['Press militar', 'hombros', 3, '8-10', true, ''],
                ['Elevaciones laterales', 'hombros', 4, '12-20', false, ''],
                ['Tríceps soga', 'triceps', 3, '10-15', false, 'Extensión en polea con soga.'],
                ['Extensión de tríceps sobre la cabeza', 'triceps', 3, '10-15', false, ''],
            ]],
            ['Día 2 — Espalda + bíceps', [
                ['Jalón al pecho', 'espalda', 4, '8-12', true, ''],
                ['Remo con barra', 'espalda', 4, '6-10', true, ''],
                ['Remo con mancuerna a una mano', 'espalda', 3, '8-12', true, 'Remo unilateral.'],
                ['Remo en polea baja', 'espalda', 3, '10-12', true, 'Alternativa: remo en máquina.'],
                ['Pullover en polea', 'espalda', 2, '12-15', false, ''],
                ['Face pull', 'hombros', 3, '15-20', false, 'Alternativa: pájaros.'],
                ['Curl inclinado', 'biceps', 3, '8-12', false, ''],
                ['Curl martillo', 'biceps', 3, '10-12', false, ''],
            ]],
            ['Día 3 — Piernas', [
                ['Sentadilla con barra', 'piernas', 4, '6-10', true, ''],
                ['Peso muerto rumano', 'gluteos', 3, '8-10', true, ''],
                ['Prensa de piernas', 'piernas', 3, '10-15', true, ''],
                ['Zancadas caminando', 'piernas', 3, '10-12 por pierna', true, 'Estocadas; alternativa: sentadilla búlgara.'],
                ['Extensiones de cuádriceps', 'piernas', 3, '12-15', false, ''],
                ['Curl femoral', 'piernas', 4, '10-15', false, ''],
                ['Gemelos', 'piernas', 4, '10-15', false, ''],
            ]],
            ['Día 4 — Hombros + brazos', [
                ['Press militar con mancuernas', 'hombros', 3, '8-10', true, ''],
                ['Elevaciones laterales', 'hombros', 4, '12-20', false, ''],
                ['Posterior peck deck', 'hombros', 4, '12-20', false, 'Peck deck inverso para deltoides posteriores.'],
                ['Curl Scott', 'biceps', 3, '8-12', false, ''],
                ['Curl martillo', 'biceps', 3, '10-12', false, ''],
                ['Curl en polea', 'biceps', 2, '12-15', false, ''],
                ['Press francés', 'triceps', 3, '8-12', false, 'Alternativa: extensión por encima de la cabeza.'],
                ['Extensión de tríceps en polea', 'triceps', 3, '10-15', false, ''],
                ['Fondos en paralelas', 'triceps', 2, '8-15', true, ''],
            ]],
            ['Día 5 — Torso completo', [
                ['Press inclinado Smith', 'pecho', 3, '8-10', true, ''],
                ['Jalón al pecho', 'espalda', 3, '8-12', true, ''],
                ['Remo en máquina', 'espalda', 3, '8-12', true, ''],
                ['Peck deck', 'pecho', 2, '12-15', false, ''],
                ['Elevaciones laterales', 'hombros', 3, '15-20', false, ''],
                ['Curl bíceps con barra', 'biceps', 2, '10-15', false, 'Curl de bíceps.'],
                ['Extensión de tríceps en polea', 'triceps', 2, '10-15', false, ''],
                ['Crunch', 'abdomen', 3, '10-20', false, 'Abdominales: 3 series, opcional una cuarta (3–4 en total).'],
            ]],
        ];
    }
}
