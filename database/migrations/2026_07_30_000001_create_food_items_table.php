<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('food_items', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('category')->nullable();

            // Macros de referencia cada 100g (o 100ml para líquidos), para poder
            // calcular cualquier cantidad que cargue el alumno.
            $table->decimal('calories_per_100g', 7, 1);
            $table->decimal('protein_per_100g', 6, 1)->default(0);
            $table->decimal('carbs_per_100g', 6, 1)->default(0);
            $table->decimal('fat_per_100g', 6, 1)->default(0);

            // Solo super_admin puede crear alimentos globales (visibles en todos los gimnasios).
            $table->boolean('is_global')->default(false);

            $table->foreignId('created_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_global', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('food_items');
    }
};
