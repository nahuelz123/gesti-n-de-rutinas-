<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Routine;

class AssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $carlos = User::where('email', 'coach.carlos@visionfit.com')->value('id');
        $laura  = User::where('email', 'coach.laura@visionfit.com')->value('id');
        $martin = User::where('email', 'coach.martin@visionfit.com')->value('id');

        $lucas     = User::where('email', 'lucas@cliente.com')->value('id');
        $sofia     = User::where('email', 'sofia@cliente.com')->value('id');
        $matias    = User::where('email', 'matias@cliente.com')->value('id');
        $valentina = User::where('email', 'valentina@cliente.com')->value('id');
        $agustin   = User::where('email', 'agustin@cliente.com')->value('id');
        $camila    = User::where('email', 'camila@cliente.com')->value('id');

        $r1 = Routine::where('title', 'Full Body Principiante (3 días)')->value('id');
        $r2 = Routine::where('title', 'Hipertrofia Torso / Pierna (4 días)')->value('id');
        $r3 = Routine::where('title', 'Glúteos y Core (3 días)')->value('id');
        $r4 = Routine::where('title', 'Fuerza Base (5 días)')->value('id');

        $assignments = [

            // Lucas: tuvo Full Body (completada) y ahora tiene Hipertrofia (activa)
            [
                'gym_id'         => 1,
                'routine_id'     => $r1,
                'client_id'      => $lucas,
                'assigned_by_id' => $carlos,
                'assigned_at'    => now()->subMonths(3),
                'start_date'     => now()->subMonths(3)->toDateString(),
                'end_date'       => now()->subMonths(1)->toDateString(),
                'status'         => 'completed',
                'notes'          => 'Completó bien la etapa de adaptación.',
                'created_at'     => now()->subMonths(3),
                'updated_at'     => now()->subMonths(1),
            ],
            [
                'gym_id'         => 1,
                'routine_id'     => $r2,
                'client_id'      => $lucas,
                'assigned_by_id' => $carlos,
                'assigned_at'    => now()->subMonths(1),
                'start_date'     => now()->subMonths(1)->toDateString(),
                'end_date'       => null,
                'status'         => 'active',
                'notes'          => null,
                'created_at'     => now()->subMonths(1),
                'updated_at'     => now()->subMonths(1),
            ],

            // Sofía: activa con Glúteos y Core
            [
                'gym_id'         => 1,
                'routine_id'     => $r3,
                'client_id'      => $sofia,
                'assigned_by_id' => $laura,
                'assigned_at'    => now()->subWeeks(3),
                'start_date'     => now()->subWeeks(3)->toDateString(),
                'end_date'       => null,
                'status'         => 'active',
                'notes'          => 'Cuidado con la rodilla derecha.',
                'created_at'     => now()->subWeeks(3),
                'updated_at'     => now()->subWeeks(3),
            ],

            // Matías: Full Body activa
            [
                'gym_id'         => 1,
                'routine_id'     => $r1,
                'client_id'      => $matias,
                'assigned_by_id' => $carlos,
                'assigned_at'    => now()->subWeeks(2),
                'start_date'     => now()->subWeeks(2)->toDateString(),
                'end_date'       => null,
                'status'         => 'active',
                'notes'          => null,
                'created_at'     => now()->subWeeks(2),
                'updated_at'     => now()->subWeeks(2),
            ],

            // Valentina: Glúteos y Core activa
            [
                'gym_id'         => 1,
                'routine_id'     => $r3,
                'client_id'      => $valentina,
                'assigned_by_id' => $laura,
                'assigned_at'    => now()->subWeeks(4),
                'start_date'     => now()->subWeeks(4)->toDateString(),
                'end_date'       => null,
                'status'         => 'active',
                'notes'          => null,
                'created_at'     => now()->subWeeks(4),
                'updated_at'     => now()->subWeeks(4),
            ],

            // Agustín (Gym 2): Fuerza Base activa
            [
                'gym_id'         => 2,
                'routine_id'     => $r4,
                'client_id'      => $agustin,
                'assigned_by_id' => $martin,
                'assigned_at'    => now()->subWeeks(5),
                'start_date'     => now()->subWeeks(5)->toDateString(),
                'end_date'       => null,
                'status'         => 'active',
                'notes'          => null,
                'created_at'     => now()->subWeeks(5),
                'updated_at'     => now()->subWeeks(5),
            ],

            // Camila (Gym 2): Fuerza Base activa
            [
                'gym_id'         => 2,
                'routine_id'     => $r4,
                'client_id'      => $camila,
                'assigned_by_id' => $martin,
                'assigned_at'    => now()->subWeeks(2),
                'start_date'     => now()->subWeeks(2)->toDateString(),
                'end_date'       => null,
                'status'         => 'active',
                'notes'          => null,
                'created_at'     => now()->subWeeks(2),
                'updated_at'     => now()->subWeeks(2),
            ],
        ];

        DB::table('assignments')->insert($assignments);
    }
}