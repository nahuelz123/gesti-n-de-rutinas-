<?php

use Database\Seeders\NahuelHypertrophyRoutineSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Ejecutar solo este seeder al desplegar, sin seeders de datos de ejemplo.
        (new NahuelHypertrophyRoutineSeeder)->run();
    }

    public function down(): void
    {
        // Conservar la rutina y los cambios posteriores realizados por el profesor.
    }
};
