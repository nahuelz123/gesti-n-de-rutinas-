<?php

namespace Database\Seeders;

use App\Models\Exercise;
use Illuminate\Database\Seeder;

class GlobalCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $exercises = [
            ['title' => 'Press banca', 'muscle_group' => 'pecho'],
            ['title' => 'Press inclinado', 'muscle_group' => 'pecho'],
            ['title' => 'Aperturas', 'muscle_group' => 'pecho'],
            ['title' => 'Fondos', 'muscle_group' => 'pecho'],
            
            ['title' => 'Dominadas', 'muscle_group' => 'espalda'],
            ['title' => 'Jalón al pecho', 'muscle_group' => 'espalda'],
            ['title' => 'Remo con barra', 'muscle_group' => 'espalda'],
            
            ['title' => 'Press militar', 'muscle_group' => 'hombros'],
            ['title' => 'Elevaciones laterales', 'muscle_group' => 'hombros'],
            ['title' => 'Pájaros', 'muscle_group' => 'hombros'],
            
            ['title' => 'Curl con barra', 'muscle_group' => 'biceps'],
            ['title' => 'Curl con mancuernas', 'muscle_group' => 'biceps'],
            ['title' => 'Curl martillo', 'muscle_group' => 'biceps'],
            
            ['title' => 'Extensión en polea', 'muscle_group' => 'triceps'],
            ['title' => 'Press francés', 'muscle_group' => 'triceps'],
            
            ['title' => 'Sentadilla', 'muscle_group' => 'piernas'],
            ['title' => 'Prensa', 'muscle_group' => 'piernas'],
            ['title' => 'Peso muerto rumano', 'muscle_group' => 'piernas'],
            ['title' => 'Hip thrust', 'muscle_group' => 'gluteos'],
            ['title' => 'Extensión de cuádriceps', 'muscle_group' => 'piernas'],
            ['title' => 'Curl femoral', 'muscle_group' => 'piernas'],
            ['title' => 'Abductores', 'muscle_group' => 'piernas'],
            ['title' => 'Aductores', 'muscle_group' => 'piernas'],
            ['title' => 'Gemelos', 'muscle_group' => 'piernas'],
        ];

        foreach ($exercises as $ex) {
            Exercise::firstOrCreate(
                ['title' => $ex['title'], 'is_global' => true, 'gym_id' => null],
                ['muscle_group' => $ex['muscle_group'], 'created_by_id' => null]
            );
        }
    }
}
