<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipe_ingredients', function (Blueprint $table) {
            $table->id();

            $table->foreignId('recipe_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->decimal('quantity', 7, 2)->nullable();
            $table->string('unit')->nullable(); // gr, ml, taza, unidad, cdita, etc.
            $table->unsignedTinyInteger('order')->default(0);

            $table->timestamps();

            $table->index(['recipe_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_ingredients');
    }
};