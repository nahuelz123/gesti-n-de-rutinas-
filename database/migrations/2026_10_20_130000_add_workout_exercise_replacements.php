<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exercise_logs', function (Blueprint $table) {
            // Null preserves the original routine exercise for pre-existing logs.
            $table->foreignId('exercise_id')->nullable()->constrained('exercises')->restrictOnDelete();
        });

        Schema::create('workout_exercise_replacements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('routine_day_exercise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained()->restrictOnDelete();
            $table->date('performed_on');
            $table->timestamps();
            $table->unique(['assignment_id', 'routine_day_exercise_id', 'performed_on'], 'workout_replacements_slot_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_exercise_replacements');
        Schema::table('exercise_logs', fn (Blueprint $table) => $table->dropConstrainedForeignId('exercise_id'));
    }
};
