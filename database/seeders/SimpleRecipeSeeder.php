<?php

namespace Database\Seeders;

use App\Models\FoodItem;
use App\Models\Recipe;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SimpleRecipeSeeder extends Seeder
{
    public function run(): void
    {
        // Gramos por porción. Los valores nutricionales se calculan desde el catálogo.
        $recipes = [
            ['Ensalada de atún y tomate', 'almuerzo', [['Atún al natural (escurrido)', 120], ['Tomate', 150], ['Aceite de oliva', 10]]],
            ['Pollo con arroz y brócoli', 'almuerzo', [['Pechuga de pollo (cocida)', 150], ['Arroz blanco (cocido)', 150], ['Brócoli (cocido)', 120]]],
            ['Merluza con papa y zanahoria', 'cena', [['Merluza (cocida)', 160], ['Papa (cocida)', 200], ['Zanahoria', 100]]],
            ['Yogur con banana y avena', 'desayuno', [['Yogur griego descremado', 180], ['Banana', 100], ['Avena (en hojuelas, cruda)', 40]]],
            ['Ensalada de garbanzos y pepino', 'almuerzo', [['Garbanzos (cocidos)', 180], ['Pepino', 100], ['Tomate', 100], ['Aceite de oliva', 10]]],
            ['Tostada con huevo y tomate', 'desayuno', [['Pan integral', 80], ['Huevo entero', 100], ['Tomate', 80]]],
            ['Lentejas con arroz', 'almuerzo', [['Lentejas (cocidas)', 180], ['Arroz integral (cocido)', 120], ['Cebolla', 50]]],
            ['Pasta con pollo y tomate', 'almuerzo', [['Fideos / pasta (cocidos)', 180], ['Pechuga de pollo (cocida)', 120], ['Tomate', 120]]],
            ['Bowl de quinoa y palta', 'cena', [['Quinoa (cocida)', 180], ['Palta', 70], ['Tomate', 120]]],
            ['Salmón con espinaca', 'cena', [['Salmón (cocido)', 150], ['Espinaca (cruda)', 100], ['Aceite de oliva', 5]]],
            ['Batido de leche y frutilla', 'merienda', [['Leche descremada', 250], ['Frutilla', 150], ['Avena (en hojuelas, cruda)', 30]]],
            ['Ensalada de pollo y morrón', 'almuerzo', [['Pechuga de pollo (cocida)', 180], ['Morrón rojo', 100], ['Tomate', 100]]],
        ];

        DB::transaction(function () use ($recipes): void {
            foreach ($recipes as [$title, $mealType, $ingredients]) {
                $foods = collect($ingredients)->map(fn ($ingredient) => [FoodItem::where('name', $ingredient[0])->where('is_global', true)->first(), $ingredient[1]]);
                if ($foods->contains(fn ($row) => $row[0] === null)) {
                    continue;
                }
                $totals = ['calories' => 0, 'protein' => 0, 'carbs' => 0, 'fat' => 0];
                foreach ($foods as [$food, $grams]) {
                    foreach ($food->macrosFor($grams) as $key => $value) {
                        $totals[$key] += $value;
                    }
                }
                $recipe = Recipe::firstOrCreate(['title' => $title, 'is_global' => true], $totals + [
                    'meal_type' => $mealType, 'servings' => 1, 'prep_time' => 20,
                    'description' => 'Porción individual. Valores nutricionales aproximados; verificá la preparación y las etiquetas de los productos.',
                    'created_by_id' => null,
                ]);
                if ($recipe->wasRecentlyCreated) {
                    foreach ($ingredients as $index => [$name, $grams]) {
                        $recipe->ingredients()->create(['name' => $name, 'quantity' => $grams, 'unit' => 'g', 'order' => $index]);
                    }
                    $recipe->instructions()->create(['step' => 1, 'instruction' => 'Preparar los ingredientes según su presentación indicada y combinarlos. Cocinar completamente los ingredientes que lo requieran.']);
                }
            }
        });
    }
}
