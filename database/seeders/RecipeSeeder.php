<?php

namespace Database\Seeders;

use App\Models\Recipe;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RecipeSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            foreach ($this->recipes() as $item) {
                $recipe = Recipe::updateOrCreate(
                    ['title' => $item['title']],
                    [
                        'description' => $item['description'] ?? null,
                        'photo_url' => null,
                        'video_url' => null,
                        'calories' => $item['calories'],
                        'protein' => $item['protein'],
                        'carbs' => $item['carbs'],
                        'fat' => $item['fat'],
                        'prep_time' => $item['prep_time'],
                        'servings' => 1,
                        'meal_type' => $item['meal_type'],
                        'is_global' => true,
                        'created_by_id' => null,
                    ]
                );

                $recipe->ingredients()->delete();
                foreach ($item['ingredients'] as $order => $ingredient) {
                    $recipe->ingredients()->create([
                        'name' => $ingredient['name'],
                        'quantity' => $ingredient['quantity'] ?? null,
                        'unit' => $ingredient['unit'] ?? null,
                        'order' => $order,
                    ]);
                }

                $recipe->instructions()->delete();
                foreach ($item['instructions'] as $i => $instruction) {
                    $recipe->instructions()->create([
                        'step' => $i + 1,
                        'instruction' => $instruction,
                    ]);
                }
            }
        });
    }

    private function recipes(): array
    {
        return [
            // ─── DESAYUNO ────────────────────────────────────────────
            [
                'title' => 'Avena con banana y miel',
                'meal_type' => 'desayuno',
                'calories' => 350, 'protein' => 12, 'carbs' => 55, 'fat' => 8,
                'prep_time' => 5,
                'ingredients' => [
                    ['name' => 'Avena', 'quantity' => 60, 'unit' => 'gr'],
                    ['name' => 'Banana', 'quantity' => 1, 'unit' => 'unidad'],
                    ['name' => 'Miel', 'quantity' => 1, 'unit' => 'cdita'],
                    ['name' => 'Leche descremada', 'quantity' => 200, 'unit' => 'ml'],
                ],
                'instructions' => [
                    'Cocinar la avena con la leche a fuego bajo durante 5 minutos.',
                    'Agregar la banana en rodajas.',
                    'Endulzar con miel al servir.',
                ],
            ],
            [
                'title' => 'Tostadas con palta y huevo',
                'meal_type' => 'desayuno',
                'calories' => 320, 'protein' => 16, 'carbs' => 28, 'fat' => 16,
                'prep_time' => 10,
                'ingredients' => [
                    ['name' => 'Pan integral', 'quantity' => 2, 'unit' => 'unidad'],
                    ['name' => 'Palta', 'quantity' => 0.5, 'unit' => 'unidad'],
                    ['name' => 'Huevo', 'quantity' => 2, 'unit' => 'unidad'],
                    ['name' => 'Sal y pimienta', 'quantity' => null, 'unit' => 'a gusto'],
                ],
                'instructions' => [
                    'Tostar el pan.',
                    'Pisar la palta y untar sobre las tostadas.',
                    'Cocinar los huevos a gusto (revueltos o fritos) y servir encima.',
                ],
            ],
            [
                'title' => 'Yogur con granola y frutos rojos',
                'meal_type' => 'desayuno',
                'calories' => 280, 'protein' => 14, 'carbs' => 38, 'fat' => 7,
                'prep_time' => 3,
                'ingredients' => [
                    ['name' => 'Yogur natural descremado', 'quantity' => 200, 'unit' => 'gr'],
                    ['name' => 'Granola', 'quantity' => 30, 'unit' => 'gr'],
                    ['name' => 'Frutos rojos', 'quantity' => 50, 'unit' => 'gr'],
                ],
                'instructions' => [
                    'Colocar el yogur en un bowl.',
                    'Agregar la granola y los frutos rojos.',
                    'Mezclar suavemente y servir.',
                ],
            ],

            // ─── ALMUERZO ────────────────────────────────────────────
            [
                'title' => 'Pollo grillado con arroz y vegetales',
                'meal_type' => 'almuerzo',
                'calories' => 520, 'protein' => 45, 'carbs' => 55, 'fat' => 10,
                'prep_time' => 25,
                'ingredients' => [
                    ['name' => 'Pechuga de pollo', 'quantity' => 200, 'unit' => 'gr'],
                    ['name' => 'Arroz integral (crudo)', 'quantity' => 80, 'unit' => 'gr'],
                    ['name' => 'Brócoli', 'quantity' => 100, 'unit' => 'gr'],
                    ['name' => 'Zanahoria', 'quantity' => 80, 'unit' => 'gr'],
                    ['name' => 'Aceite de oliva', 'quantity' => 1, 'unit' => 'cda'],
                ],
                'instructions' => [
                    'Cocinar el arroz según las indicaciones del paquete.',
                    'Grillar la pechuga de pollo con sal y especias a gusto.',
                    'Saltear los vegetales en el aceite de oliva.',
                    'Servir todo junto.',
                ],
            ],
            [
                'title' => 'Carne magra con batata y ensalada',
                'meal_type' => 'almuerzo',
                'calories' => 480, 'protein' => 40, 'carbs' => 45, 'fat' => 12,
                'prep_time' => 30,
                'ingredients' => [
                    ['name' => 'Carne magra (nalga o peceto)', 'quantity' => 180, 'unit' => 'gr'],
                    ['name' => 'Batata', 'quantity' => 200, 'unit' => 'gr'],
                    ['name' => 'Lechuga y tomate', 'quantity' => null, 'unit' => 'a gusto'],
                    ['name' => 'Aceite de oliva', 'quantity' => 1, 'unit' => 'cda'],
                ],
                'instructions' => [
                    'Cocinar la batata al horno o hervida.',
                    'Sellar la carne a la plancha hasta el punto deseado.',
                    'Armar la ensalada y condimentar con aceite de oliva.',
                    'Servir todo junto.',
                ],
            ],
            [
                'title' => 'Ensalada de atún y garbanzos',
                'meal_type' => 'almuerzo',
                'calories' => 420, 'protein' => 32, 'carbs' => 38, 'fat' => 14,
                'prep_time' => 10,
                'ingredients' => [
                    ['name' => 'Atún al natural', 'quantity' => 1, 'unit' => 'lata'],
                    ['name' => 'Garbanzos cocidos', 'quantity' => 150, 'unit' => 'gr'],
                    ['name' => 'Tomate', 'quantity' => 1, 'unit' => 'unidad'],
                    ['name' => 'Cebolla morada', 'quantity' => 0.25, 'unit' => 'unidad'],
                    ['name' => 'Aceite de oliva', 'quantity' => 1, 'unit' => 'cda'],
                ],
                'instructions' => [
                    'Escurrir el atún.',
                    'Mezclar con los garbanzos, tomate y cebolla picados.',
                    'Condimentar con aceite de oliva, sal y jugo de limón.',
                ],
            ],

            // ─── MERIENDA ────────────────────────────────────────────
            [
                'title' => 'Licuado de banana y avena',
                'meal_type' => 'merienda',
                'calories' => 250, 'protein' => 10, 'carbs' => 40, 'fat' => 5,
                'prep_time' => 5,
                'ingredients' => [
                    ['name' => 'Banana', 'quantity' => 1, 'unit' => 'unidad'],
                    ['name' => 'Avena', 'quantity' => 30, 'unit' => 'gr'],
                    ['name' => 'Leche descremada', 'quantity' => 250, 'unit' => 'ml'],
                    ['name' => 'Canela', 'quantity' => null, 'unit' => 'a gusto'],
                ],
                'instructions' => [
                    'Colocar todos los ingredientes en la licuadora.',
                    'Licuar hasta lograr una textura homogénea.',
                    'Servir frío.',
                ],
            ],
            [
                'title' => 'Tostado de jamón y queso light',
                'meal_type' => 'merienda',
                'calories' => 300, 'protein' => 18, 'carbs' => 30, 'fat' => 10,
                'prep_time' => 8,
                'ingredients' => [
                    ['name' => 'Pan lactal integral', 'quantity' => 2, 'unit' => 'unidad'],
                    ['name' => 'Jamón cocido light', 'quantity' => 2, 'unit' => 'feta'],
                    ['name' => 'Queso port salut light', 'quantity' => 2, 'unit' => 'feta'],
                ],
                'instructions' => [
                    'Armar el sándwich con el jamón y el queso.',
                    'Tostar en sandwichera o plancha hasta dorar.',
                ],
            ],

            // ─── CENA ────────────────────────────────────────────────
            [
                'title' => 'Salmón al horno con puré de calabaza',
                'meal_type' => 'cena',
                'calories' => 460, 'protein' => 38, 'carbs' => 30, 'fat' => 20,
                'prep_time' => 30,
                'ingredients' => [
                    ['name' => 'Filet de salmón', 'quantity' => 180, 'unit' => 'gr'],
                    ['name' => 'Calabaza', 'quantity' => 250, 'unit' => 'gr'],
                    ['name' => 'Aceite de oliva', 'quantity' => 1, 'unit' => 'cda'],
                    ['name' => 'Sal y pimienta', 'quantity' => null, 'unit' => 'a gusto'],
                ],
                'instructions' => [
                    'Hornear el salmón con sal, pimienta y aceite durante 15-20 minutos.',
                    'Hervir y pisar la calabaza hasta lograr un puré.',
                    'Servir juntos.',
                ],
            ],
            [
                'title' => 'Tortilla de vegetales',
                'meal_type' => 'cena',
                'calories' => 340, 'protein' => 22, 'carbs' => 18, 'fat' => 20,
                'prep_time' => 20,
                'ingredients' => [
                    ['name' => 'Huevo', 'quantity' => 3, 'unit' => 'unidad'],
                    ['name' => 'Zucchini', 'quantity' => 100, 'unit' => 'gr'],
                    ['name' => 'Cebolla', 'quantity' => 0.5, 'unit' => 'unidad'],
                    ['name' => 'Aceite de oliva', 'quantity' => 1, 'unit' => 'cda'],
                ],
                'instructions' => [
                    'Saltear los vegetales picados en el aceite.',
                    'Batir los huevos y agregar a la sartén.',
                    'Cocinar a fuego bajo de ambos lados hasta que cuaje.',
                ],
            ],
            [
                'title' => 'Pollo al curry con vegetales salteados',
                'meal_type' => 'cena',
                'calories' => 400, 'protein' => 36, 'carbs' => 20, 'fat' => 18,
                'prep_time' => 25,
                'ingredients' => [
                    ['name' => 'Pechuga de pollo', 'quantity' => 180, 'unit' => 'gr'],
                    ['name' => 'Curry en polvo', 'quantity' => 1, 'unit' => 'cdita'],
                    ['name' => 'Morrón', 'quantity' => 100, 'unit' => 'gr'],
                    ['name' => 'Cebolla', 'quantity' => 0.5, 'unit' => 'unidad'],
                    ['name' => 'Aceite de oliva', 'quantity' => 1, 'unit' => 'cda'],
                ],
                'instructions' => [
                    'Cortar el pollo en cubos y saltear con el curry.',
                    'Agregar los vegetales y cocinar hasta que estén tiernos.',
                    'Servir caliente.',
                ],
            ],

            // ─── PRE / POST ENTRENAMIENTO ────────────────────────────
            [
                'title' => 'Batido pre-entreno de banana y café',
                'meal_type' => 'pre_entrenamiento',
                'calories' => 220, 'protein' => 6, 'carbs' => 42, 'fat' => 3,
                'prep_time' => 5,
                'ingredients' => [
                    ['name' => 'Banana', 'quantity' => 1, 'unit' => 'unidad'],
                    ['name' => 'Café frío', 'quantity' => 100, 'unit' => 'ml'],
                    ['name' => 'Miel', 'quantity' => 1, 'unit' => 'cdita'],
                    ['name' => 'Hielo', 'quantity' => null, 'unit' => 'a gusto'],
                ],
                'instructions' => [
                    'Licuar la banana con el café y la miel.',
                    'Agregar hielo y licuar unos segundos más.',
                    'Servir de inmediato.',
                ],
            ],
            [
                'title' => 'Batido post-entreno de proteína y frutos rojos',
                'meal_type' => 'post_entrenamiento',
                'calories' => 260, 'protein' => 30, 'carbs' => 25, 'fat' => 4,
                'prep_time' => 5,
                'ingredients' => [
                    ['name' => 'Proteína en polvo (whey)', 'quantity' => 1, 'unit' => 'scoop'],
                    ['name' => 'Frutos rojos', 'quantity' => 100, 'unit' => 'gr'],
                    ['name' => 'Leche descremada', 'quantity' => 250, 'unit' => 'ml'],
                ],
                'instructions' => [
                    'Colocar todos los ingredientes en la licuadora.',
                    'Licuar hasta lograr una consistencia cremosa.',
                    'Consumir dentro de los 30 minutos post-entrenamiento.',
                ],
            ],
        ];
    }
}
