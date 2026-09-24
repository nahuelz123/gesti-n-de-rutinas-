<?php

namespace Database\Seeders;

use App\Models\FoodItem;
use Illuminate\Database\Seeder;

class CuratedFoodSeeder extends Seeder
{
    public function run(): void
    {
        $foods = json_decode(file_get_contents(database_path('data/foods_es_curated.json')), true, flags: JSON_THROW_ON_ERROR);

        foreach ($foods as $food) {
            unset($food['source_id']);
            $name = $food['name'];
            unset($food['name']);
            FoodItem::firstOrCreate(['name' => $name, 'is_global' => true], $food + ['created_by_id' => null]);
        }
    }
}
