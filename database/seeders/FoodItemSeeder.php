<?php

namespace Database\Seeders;

use App\Models\FoodItem;
use Illuminate\Database\Seeder;

class FoodItemSeeder extends Seeder
{
    /**
     * Catálogo base de alimentos con macros por 100g/100ml.
     * Idempotente: usa updateOrCreate por nombre.
     */
    public function run(): void
    {
        $foods = [
            // name, category, kcal, protein, carbs, fat (todo por 100g)
            ['Pechuga de pollo (cocida)', 'proteína', 165, 31, 0, 3.6],
            ['Carne vacuna magra (cocida)', 'proteína', 187, 27, 0, 8],
            ['Huevo entero', 'proteína', 155, 13, 1.1, 11],
            ['Clara de huevo', 'proteína', 52, 11, 0.7, 0.2],
            ['Atún al natural (escurrido)', 'proteína', 116, 26, 0, 1],
            ['Salmón (cocido)', 'proteína', 208, 20, 0, 13],
            ['Merluza (cocida)', 'proteína', 90, 19, 0, 1],
            ['Jamón cocido', 'proteína', 113, 18, 1.5, 4],
            ['Fiambre de pavo', 'proteína', 104, 17, 2, 3],
            ['Queso fresco / cottage', 'proteína', 98, 11, 3.4, 4.3],
            ['Yogur griego descremado', 'lácteo', 59, 10, 3.6, 0.4],
            ['Yogur descremado', 'lácteo', 45, 4, 6, 0.2],
            ['Leche descremada', 'lácteo', 35, 3.4, 5, 0.1],
            ['Queso port salut', 'lácteo', 320, 22, 2, 25],
            ['Arroz blanco (cocido)', 'carbohidrato', 130, 2.7, 28, 0.3],
            ['Arroz integral (cocido)', 'carbohidrato', 111, 2.6, 23, 0.9],
            ['Fideos / pasta (cocidos)', 'carbohidrato', 158, 5.8, 31, 0.9],
            ['Papa (cocida)', 'carbohidrato', 87, 2, 20, 0.1],
            ['Batata (cocida)', 'carbohidrato', 90, 2, 21, 0.1],
            ['Pan lactal blanco', 'carbohidrato', 265, 9, 49, 3.3],
            ['Pan integral', 'carbohidrato', 247, 13, 41, 3.4],
            ['Avena (en hojuelas, cruda)', 'carbohidrato', 389, 17, 66, 7],
            ['Quinoa (cocida)', 'carbohidrato', 120, 4.4, 21, 1.9],
            ['Lentejas (cocidas)', 'legumbre', 116, 9, 20, 0.4],
            ['Garbanzos (cocidos)', 'legumbre', 164, 9, 27, 2.6],
            ['Banana', 'fruta', 89, 1.1, 23, 0.3],
            ['Manzana', 'fruta', 52, 0.3, 14, 0.2],
            ['Naranja', 'fruta', 47, 0.9, 12, 0.1],
            ['Frutilla', 'fruta', 32, 0.7, 8, 0.3],
            ['Palta', 'grasa', 160, 2, 9, 15],
            ['Brócoli (cocido)', 'verdura', 35, 2.4, 7, 0.4],
            ['Espinaca (cruda)', 'verdura', 23, 2.9, 3.6, 0.4],
            ['Tomate', 'verdura', 18, 0.9, 3.9, 0.2],
            ['Zanahoria', 'verdura', 41, 0.9, 10, 0.2],
            ['Cebolla', 'verdura', 40, 1.1, 9, 0.1],
            ['Almendras', 'grasa', 579, 21, 22, 50],
            ['Nueces', 'grasa', 654, 15, 14, 65],
            ['Maní / manteca de maní', 'grasa', 588, 25, 20, 50],
            ['Aceite de oliva', 'grasa', 884, 0, 0, 100],
            ['Chía (semillas)', 'grasa', 486, 17, 42, 31],
            ['Miel', 'otro', 304, 0.3, 82, 0],
            ['Azúcar', 'otro', 387, 0, 100, 0],
        ];

        foreach ($foods as [$name, $category, $kcal, $protein, $carbs, $fat]) {
            FoodItem::updateOrCreate(
                ['name' => $name],
                [
                    'category' => $category,
                    'calories_per_100g' => $kcal,
                    'protein_per_100g' => $protein,
                    'carbs_per_100g' => $carbs,
                    'fat_per_100g' => $fat,
                    'is_global' => true,
                    'created_by_id' => null,
                ]
            );
        }
    }
}
