<?php

namespace Database\Seeders;

use App\Models\DietPlan;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Seeder;

class DietPlanSeeder extends Seeder
{
    public function run(): void
    {
        $carlosId = User::where('email', 'coach.carlos@visionfit.com')->value('id');
        $martinId = User::where('email', 'coach.martin@visionfit.com')->value('id');

        $recipe = fn (string $title) => Recipe::where('title', $title)->value('id');

        // ── PLAN 1: Gym Central (Carlos) ───────────────────────────────
        $plan1 = DietPlan::updateOrCreate(
            ['title' => 'Plan Equilibrado (5000 kcal semanales)', 'gym_id' => 1],
            [
                'coach_id' => $carlosId,
                'description' => 'Plan de mantenimiento con macros balanceados. Incluye pre/post entreno los días de fuerza.',
                'goal' => 'mantenimiento',
                'target_calories' => 2100,
            ]
        );

        $diasEntreno = ['lunes', 'miercoles', 'viernes'];
        $diasSemana = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];

        $desayunos = ['Avena con banana y miel', 'Tostadas con palta y huevo', 'Yogur con granola y frutos rojos'];
        $almuerzos = ['Pollo grillado con arroz y vegetales', 'Carne magra con batata y ensalada', 'Ensalada de atún y garbanzos'];
        $meriendas = ['Licuado de banana y avena', 'Tostado de jamón y queso light'];
        $cenas = ['Salmón al horno con puré de calabaza', 'Tortilla de vegetales', 'Pollo al curry con vegetales salteados'];

        foreach ($diasSemana as $i => $dayKey) {
            $day = $plan1->days()->updateOrCreate(['day_of_week' => $dayKey]);

            $day->recipes()->delete();

            $order = 1;

            $day->recipes()->create([
                'recipe_id' => $recipe($desayunos[$i % count($desayunos)]),
                'meal_type' => 'desayuno',
                'order' => $order++,
                'servings' => 1,
            ]);

            if (in_array($dayKey, $diasEntreno, true)) {
                $day->recipes()->create([
                    'recipe_id' => $recipe('Batido pre-entreno de banana y café'),
                    'meal_type' => 'pre_entrenamiento',
                    'order' => $order++,
                    'servings' => 1,
                ]);
            }

            $day->recipes()->create([
                'recipe_id' => $recipe($almuerzos[$i % count($almuerzos)]),
                'meal_type' => 'almuerzo',
                'order' => $order++,
                'servings' => 1,
            ]);

            if (in_array($dayKey, $diasEntreno, true)) {
                $day->recipes()->create([
                    'recipe_id' => $recipe('Batido post-entreno de proteína y frutos rojos'),
                    'meal_type' => 'post_entrenamiento',
                    'order' => $order++,
                    'servings' => 1,
                ]);
            }

            $day->recipes()->create([
                'recipe_id' => $recipe($meriendas[$i % count($meriendas)]),
                'meal_type' => 'merienda',
                'order' => $order++,
                'servings' => 1,
            ]);

            $day->recipes()->create([
                'recipe_id' => $recipe($cenas[$i % count($cenas)]),
                'meal_type' => 'cena',
                'order' => $order++,
                'servings' => 1,
            ]);
        }

        // ── PLAN 2: Gym Norte (Martín) ──────────────────────────────────
        $plan2 = DietPlan::updateOrCreate(
            ['title' => 'Plan Alto en Proteína (ganancia muscular)', 'gym_id' => 2],
            [
                'coach_id' => $martinId,
                'description' => 'Plan orientado a ganancia muscular, con énfasis en proteína en cada comida.',
                'goal' => 'ganancia_muscular',
                'target_calories' => 2600,
            ]
        );

        $diasPlan2 = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];

        foreach ($diasPlan2 as $i => $dayKey) {
            $day = $plan2->days()->updateOrCreate(['day_of_week' => $dayKey]);

            $day->recipes()->delete();

            $order = 1;

            $day->recipes()->create([
                'recipe_id' => $recipe('Tostadas con palta y huevo'),
                'meal_type' => 'desayuno',
                'order' => $order++,
                'servings' => 1.5,
            ]);

            $day->recipes()->create([
                'recipe_id' => $recipe('Pollo grillado con arroz y vegetales'),
                'meal_type' => 'almuerzo',
                'order' => $order++,
                'servings' => 1.5,
            ]);

            $day->recipes()->create([
                'recipe_id' => $recipe('Batido post-entreno de proteína y frutos rojos'),
                'meal_type' => 'post_entrenamiento',
                'order' => $order++,
                'servings' => 1,
            ]);

            $day->recipes()->create([
                'recipe_id' => $recipe('Salmón al horno con puré de calabaza'),
                'meal_type' => 'cena',
                'order' => $order++,
                'servings' => 1.2,
            ]);
        }
    }
}
