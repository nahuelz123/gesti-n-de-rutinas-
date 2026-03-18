<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exercise_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('assignment_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('routine_day_exercise_id')
                ->constrained()
                ->cascadeOnDelete();

            // Número de serie (1,2,3...)
            $table->unsignedTinyInteger('set_number');

            // Peso levantado
            $table->decimal('weight', 6, 2)->nullable();

            // Repeticiones reales hechas
            $table->unsignedTinyInteger('reps')->nullable();

            // Momento exacto del log
            $table->timestamp('logged_at')->useCurrent();

            $table->timestamps();

            // Índices clave para progreso y gráficos
            $table->index(['assignment_id']);
            $table->index(['routine_day_exercise_id']);
            $table->index(['logged_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exercise_logs');
    }
};
