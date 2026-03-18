<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Exercise;

class RoutineSeeder extends Seeder
{
    public function run(): void
    {
        // Coaches
        $carlosId = User::where('email', 'coach.carlos@visionfit.com')->value('id');
        $lauraId  = User::where('email', 'coach.laura@visionfit.com')->value('id');
        $martinId = User::where('email', 'coach.martin@visionfit.com')->value('id');

        // Helper para buscar exercise_id por titulo
        $ex = fn($title) => Exercise::where('title', $title)->value('id');

        // ── RUTINA 1: Full Body Principiante (Gym 1) ──────────────────────
        $r1 = DB::table('routines')->insertGetId([
            'gym_id'      => 1,
            'coach_id'    => $carlosId,
            'title'       => 'Full Body Principiante (3 días)',
            'description' => 'Adaptación general, técnica y progresión suave.',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $d1 = DB::table('routine_days')->insertGetId([
            'routine_id' => $r1, 'day_number' => 1, 'title' => 'Full Body A',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->addExercises($d1, [
            [$ex('Sentadilla con barra'),       3, '8-10', '2 min',  'Técnica primero.', 1],
            [$ex('Press banca con barra'),       3, '8-10', '2 min',  null,               2],
            [$ex('Remo con barra'),              3, '8-10', '2 min',  null,               3],
            [$ex('Plancha'),                     3, '30-45s', '60s',  'Core apretado.',   4],
        ]);

        $d2 = DB::table('routine_days')->insertGetId([
            'routine_id' => $r1, 'day_number' => 2, 'title' => 'Full Body B',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->addExercises($d2, [
            [$ex('Peso muerto convencional'),    3, '6-8',  '2-3 min', 'Lento y controlado.', 1],
            [$ex('Press militar'),               3, '8-10', '2 min',   null,                  2],
            [$ex('Jalón al pecho'),              3, '10-12','90s',     null,                  3],
            [$ex('Crunch'),                      3, '15',   '60s',     null,                  4],
        ]);

        $d3 = DB::table('routine_days')->insertGetId([
            'routine_id' => $r1, 'day_number' => 3, 'title' => 'Full Body C',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->addExercises($d3, [
            [$ex('Prensa de piernas'),           3, '10-12', '90s',  null, 1],
            [$ex('Aperturas con mancuernas'),    3, '12',    '60s',  null, 2],
            [$ex('Remo con mancuerna a una mano'),3,'10',    '60s',  null, 3],
            [$ex('Elevación de piernas'),        3, '15',    '60s',  null, 4],
        ]);

        // ── RUTINA 2: Hipertrofia Torso/Pierna (Gym 1) ────────────────────
        $r2 = DB::table('routines')->insertGetId([
            'gym_id'      => 1,
            'coach_id'    => $carlosId,
            'title'       => 'Hipertrofia Torso / Pierna (4 días)',
            'description' => 'Rutina orientada a ganancia muscular. Compuestos + accesorios. Progresión semana a semana.',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $d4 = DB::table('routine_days')->insertGetId([
            'routine_id' => $r2, 'day_number' => 1, 'title' => 'Torso A',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->addExercises($d4, [
            [$ex('Press banca con barra'),           4, '6-8',   '2 min',  null, 1],
            [$ex('Remo con barra'),                  4, '8-10',  '2 min',  null, 2],
            [$ex('Press militar'),                   3, '6-8',   '2 min',  null, 3],
            [$ex('Curl bíceps con barra'),           3, '10-12', '90s',   null, 4],
            [$ex('Fondos en paralelas'),             3, '8-12',  '90s',   null, 5],
        ]);

        $d5 = DB::table('routine_days')->insertGetId([
            'routine_id' => $r2, 'day_number' => 2, 'title' => 'Pierna A',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->addExercises($d5, [
            [$ex('Sentadilla con barra'),            4, '5-8',   '2-3 min', null, 1],
            [$ex('Prensa de piernas'),               3, '10-12', '90s',    null, 2],
            [$ex('Curl femoral'),                    3, '10-12', '90s',    null, 3],
            [$ex('Plancha'),                         3, '30-45s','60s',    null, 4],
        ]);

        $d6 = DB::table('routine_days')->insertGetId([
            'routine_id' => $r2, 'day_number' => 3, 'title' => 'Torso B',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->addExercises($d6, [
            [$ex('Press banca inclinado con mancuernas'), 4, '8-10', '2 min', null, 1],
            [$ex('Jalón al pecho'),                       4, '8-10', '2 min', null, 2],
            [$ex('Elevaciones laterales'),                3, '12-15','60s',   null, 3],
            [$ex('Curl martillo'),                        3, '10-12','60s',   null, 4],
            [$ex('Extensión de tríceps en polea'),        3, '12',   '60s',   null, 5],
        ]);

        $d7 = DB::table('routine_days')->insertGetId([
            'routine_id' => $r2, 'day_number' => 4, 'title' => 'Pierna B',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->addExercises($d7, [
            [$ex('Peso muerto convencional'),     4, '5-8',   '2-3 min', null, 1],
            [$ex('Extensiones de cuádriceps'),    3, '12',    '90s',    null, 2],
            [$ex('Hip thrust con barra'),         3, '10-12', '90s',    null, 3],
            [$ex('Elevación de piernas'),         3, '15',    '60s',    null, 4],
        ]);

        // ── RUTINA 3: Glúteos y Core (Gym 1, Laura) ───────────────────────
        $r3 = DB::table('routines')->insertGetId([
            'gym_id'      => 1,
            'coach_id'    => $lauraId,
            'title'       => 'Glúteos y Core (3 días)',
            'description' => 'Programa focalizado en glúteos, isquios y core funcional.',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $d8 = DB::table('routine_days')->insertGetId([
            'routine_id' => $r3, 'day_number' => 1, 'title' => 'Glúteos A',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->addExercises($d8, [
            [$ex('Hip thrust con barra'),         4, '10-12', '90s',   null, 1],
            [$ex('Sentadilla sumo'),              3, '10-12', '90s',   null, 2],
            [$ex('Peso muerto rumano'),           3, '10-12', '90s',   null, 3],
            [$ex('Abducción de cadera (máquina)'),3, '15',    '60s',   null, 4],
            [$ex('Plancha'),                      3, '40s',   '60s',   null, 5],
        ]);

        $d9 = DB::table('routine_days')->insertGetId([
            'routine_id' => $r3, 'day_number' => 2, 'title' => 'Core + Pierna',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->addExercises($d9, [
            [$ex('Prensa de piernas'),            3, '12',    '90s',   null, 1],
            [$ex('Curl femoral'),                 3, '12',    '90s',   null, 2],
            [$ex('Puente de glúteos'),            3, '15',    '60s',   null, 3],
            [$ex('Russian twist'),                3, '20',    '60s',   null, 4],
            [$ex('Crunch'),                       3, '15',    '60s',   null, 5],
        ]);

        $d10 = DB::table('routine_days')->insertGetId([
            'routine_id' => $r3, 'day_number' => 3, 'title' => 'Glúteos B',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->addExercises($d10, [
            [$ex('Sentadilla con barra'),         4, '8-10',  '2 min', null, 1],
            [$ex('Patada de glúteo en polea'),    3, '15',    '60s',   null, 2],
            [$ex('Zancadas caminando'),           3, '12',    '90s',   null, 3],
            [$ex('Elevación de piernas'),         3, '15',    '60s',   null, 4],
        ]);

        // ── RUTINA 4: Gym 2 (Martín) ──────────────────────────────────────
        $r4 = DB::table('routines')->insertGetId([
            'gym_id'      => 2,
            'coach_id'    => $martinId,
            'title'       => 'Fuerza Base (5 días)',
            'description' => 'Programa de fuerza con levantamientos principales.',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $d11 = DB::table('routine_days')->insertGetId([
            'routine_id' => $r4, 'day_number' => 1, 'title' => 'Press',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->addExercises($d11, [
            [$ex('Press banca con barra'),   5, '5',    '3 min', null, 1],
            [$ex('Press militar'),           3, '5',    '2 min', null, 2],
            [$ex('Fondos en paralelas'),     3, '8-10', '90s',   null, 3],
        ]);

        $d12 = DB::table('routine_days')->insertGetId([
            'routine_id' => $r4, 'day_number' => 2, 'title' => 'Sentadilla',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->addExercises($d12, [
            [$ex('Sentadilla con barra'),    5, '5',    '3 min', null, 1],
            [$ex('Prensa de piernas'),       3, '8',    '2 min', null, 2],
            [$ex('Curl femoral'),            3, '10',   '90s',   null, 3],
        ]);

        $d13 = DB::table('routine_days')->insertGetId([
            'routine_id' => $r4, 'day_number' => 3, 'title' => 'Peso muerto',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->addExercises($d13, [
            [$ex('Peso muerto convencional'), 4, '4-5', '3 min', null, 1],
            [$ex('Remo con barra'),           3, '6-8', '2 min', null, 2],
            [$ex('Dominadas pronas'),         3, '6-8', '2 min', null, 3],
        ]);
    }

    private function addExercises(int $dayId, array $exercises): void
    {
        foreach ($exercises as [$exerciseId, $sets, $reps, $rest, $notes, $order]) {
            if (!$exerciseId) continue;
            DB::table('routine_day_exercises')->insert([
                'routine_day_id' => $dayId,
                'exercise_id'    => $exerciseId,
                'sets'           => $sets,
                'reps'           => $reps,
                'rest'           => $rest,
                'notes'          => $notes,
                'order'          => $order,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }
}