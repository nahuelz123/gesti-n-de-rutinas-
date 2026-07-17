<?php

namespace Database\Seeders;

use App\Models\DietAssignment;
use App\Models\DietPlan;
use App\Models\User;
use Illuminate\Database\Seeder;

class DietAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $carlosId = User::where('email', 'coach.carlos@visionfit.com')->value('id');
        $martinId = User::where('email', 'coach.martin@visionfit.com')->value('id');

        $plan1 = DietPlan::where('title', 'Plan Equilibrado (5000 kcal semanales)')->value('id');
        $plan2 = DietPlan::where('title', 'Plan Alto en Proteína (ganancia muscular)')->value('id');

        // Clientes Gym Central: los originales del seeder + los que hayas creado a mano
        // (diego@cliente.com, micaela@cliente.com, franco@cliente.com), si existen.
        $clientesCentral = User::query()
            ->where('gym_id', 1)
            ->where('role', 'client')
            ->pluck('id');

        $clientesNorte = User::query()
            ->where('gym_id', 2)
            ->where('role', 'client')
            ->pluck('id');

        foreach ($clientesCentral as $clientId) {
            if (! $plan1 || ! $carlosId) {
                continue;
            }

            DietAssignment::updateOrCreate(
                [
                    'client_id' => $clientId,
                    'diet_plan_id' => $plan1,
                ],
                [
                    'gym_id' => 1,
                    'assigned_by_id' => $carlosId,
                    'assigned_at' => now()->subDays(5),
                    'start_date' => now()->subDays(5)->toDateString(),
                    'end_date' => null,
                    'status' => 'active',
                    'notes' => null,
                ]
            );
        }

        foreach ($clientesNorte as $clientId) {
            if (! $plan2 || ! $martinId) {
                continue;
            }

            DietAssignment::updateOrCreate(
                [
                    'client_id' => $clientId,
                    'diet_plan_id' => $plan2,
                ],
                [
                    'gym_id' => 2,
                    'assigned_by_id' => $martinId,
                    'assigned_at' => now()->subDays(3),
                    'start_date' => now()->subDays(3)->toDateString(),
                    'end_date' => null,
                    'status' => 'active',
                    'notes' => null,
                ]
            );
        }
    }
}
