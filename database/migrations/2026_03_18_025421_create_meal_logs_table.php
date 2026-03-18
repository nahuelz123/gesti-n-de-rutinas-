<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('diet_assignment_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('diet_plan_day_recipe_id')
                ->constrained()
                ->cascadeOnDelete();

            // ¿Hizo la comida?
            $table->boolean('completed')->default(false);

            // Log libre de lo que comió realmente (opcional)
            $table->text('notes')->nullable();

            // Porción real que comió
            $table->decimal('servings_eaten', 4, 1)->nullable();

            $table->date('logged_date'); // fecha del log
            $table->timestamp('logged_at')->useCurrent();

            $table->timestamps();

            $table->index(['diet_assignment_id']);
            $table->index(['diet_plan_day_recipe_id']);
            $table->index(['logged_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_logs');
    }
};