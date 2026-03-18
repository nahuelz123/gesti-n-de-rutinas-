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
        Schema::create('routine_day_exercises', function (Blueprint $table) {
            $table->id();

            $table->foreignId('routine_day_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('exercise_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('sets');
            $table->string('reps'); // ej: "8-10"
            $table->string('rest')->nullable(); // ej: "90s"
            $table->text('notes')->nullable();
            $table->unsignedTinyInteger('order')->default(0);

            $table->timestamps();

            $table->index(['routine_day_id', 'order']);
            $table->index(['exercise_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routine_day_exercises');
    }
};
