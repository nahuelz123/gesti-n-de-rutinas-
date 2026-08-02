<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('free_meal_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('client_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('food_item_id')
                ->nullable()
                ->constrained('food_items')
                ->nullOnDelete();

            // Si el alumno cargó un alimento que no está en el catálogo, guardamos
            // el nombre y los macros que ingresó a mano.
            $table->string('custom_name')->nullable();

            $table->decimal('quantity_grams', 7, 1);

            // Totales ya calculados para esa cantidad (se guardan "congelados" para
            // que el historial no cambie si el alimento se edita después).
            $table->decimal('calories', 7, 1)->default(0);
            $table->decimal('protein', 6, 1)->default(0);
            $table->decimal('carbs', 6, 1)->default(0);
            $table->decimal('fat', 6, 1)->default(0);

            $table->date('logged_date');
            $table->timestamp('logged_at');

            $table->timestamps();

            $table->index(['client_id', 'logged_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('free_meal_logs');
    }
};
