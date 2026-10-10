<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('exercises')->where('title', 'Sentadilla búlgara')->where('is_global', true)->exists()) {
            return;
        }

        DB::table('exercises')->insert([
            'title' => 'Sentadilla búlgara',
            'muscle_group' => 'piernas',
            'description' => 'Sentadilla unilateral con el pie de atrás apoyado en un banco.',
            'is_global' => true,
            'gym_id' => null,
            'created_by_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Conservar el ejercicio: puede estar referenciado por rutinas del profesor.
    }
};
