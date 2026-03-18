<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diet_plans', function (Blueprint $table) {
            $table->id();

            $table->foreignId('gym_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('coach_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            // Objetivo del plan
            $table->enum('goal', [
                'perdida_peso',
                'ganancia_muscular',
                'mantenimiento',
                'rendimiento',
            ])->nullable();

            // Calorías diarias objetivo
            $table->unsignedSmallInteger('target_calories')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['gym_id', 'coach_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diet_plans');
    }
};