<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('photo_url')->nullable();
            $table->string('video_url')->nullable();

            // Macros por porción
            $table->unsignedSmallInteger('calories')->nullable();
            $table->decimal('protein', 5, 1)->nullable();  // gramos
            $table->decimal('carbs', 5, 1)->nullable();    // gramos
            $table->decimal('fat', 5, 1)->nullable();      // gramos

            $table->unsignedTinyInteger('prep_time')->nullable(); // minutos
            $table->unsignedTinyInteger('servings')->default(1);

            $table->enum('meal_type', [
                'desayuno',
                'almuerzo',
                'merienda',
                'cena',
                'pre_entrenamiento',
                'post_entrenamiento',
            ])->nullable(); // null = sirve para cualquier comida

            $table->boolean('is_global')->default(true);
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['meal_type']);
            $table->index(['is_global']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};