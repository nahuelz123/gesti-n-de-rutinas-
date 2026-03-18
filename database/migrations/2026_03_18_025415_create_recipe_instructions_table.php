<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipe_instructions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('recipe_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('step');
            $table->text('instruction');

            $table->timestamps();

            $table->index(['recipe_id', 'step']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_instructions');
    }
};