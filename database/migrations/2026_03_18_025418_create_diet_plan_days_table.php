<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diet_plan_days', function (Blueprint $table) {
            $table->id();

            $table->foreignId('diet_plan_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('day_of_week', [
                'lunes',
                'martes',
                'miercoles',
                'jueves',
                'viernes',
                'sabado',
                'domingo',
            ]);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['diet_plan_id', 'day_of_week']);
            $table->index(['diet_plan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diet_plan_days');
    }
};