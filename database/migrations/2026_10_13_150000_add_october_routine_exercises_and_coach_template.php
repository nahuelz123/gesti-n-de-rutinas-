<?php

use Database\Seeders\NahuelOctoberRoutineSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $titles = [
            'Press pecho plano en máquina' => 'pecho',
            'Press militar en máquina' => 'hombros',
            'Press militar en Smith' => 'hombros',
            'Press cerrado con barra' => 'triceps',
            'JM press' => 'triceps',
            'Tríceps katana bilateral' => 'triceps',
            'Remo T con apoyo de pecho' => 'espalda',
            'Camilla de isquios' => 'piernas',
            'Prensa unilateral' => 'piernas',
            'Sillón invertido unilateral' => 'piernas',
        ];
        foreach ($titles as $title => $group) {
            if (DB::table('exercises')->where('title', $title)
                ->where('is_global', true)->whereNull('gym_id')->exists()) {
                continue;
            }
            DB::table('exercises')->insert([
                'gym_id' => null, 'title' => $title, 'muscle_group' => $group,
                'is_global' => true, 'created_by_id' => null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // Las rutinas existentes y las asignaciones permanecen intactas.
        (new NahuelOctoberRoutineSeeder)->run();
    }

    public function down(): void
    {
        // Conservar ejercicios y rutina: pueden estar referenciados por entrenamientos.
    }
};
