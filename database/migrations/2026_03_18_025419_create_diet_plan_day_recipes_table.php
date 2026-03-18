<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diet_plan_day_recipes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('diet_plan_day_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('recipe_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('meal_type', [
                'desayuno',
                'almuerzo',
                'merienda',
                'cena',
                'pre_entrenamiento',
                'post_entrenamiento',
            ]);

            $table->unsignedTinyInteger('order')->default(0);
            $table->text('notes')->nullable();

            // Porción personalizada para este cliente/plan
            $table->decimal('servings', 4, 1)->default(1);

            $table->timestamps();

            $table->index(['diet_plan_day_id', 'meal_type', 'order']);
            $table->index(['recipe_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diet_plan_day_recipes');
    }
};