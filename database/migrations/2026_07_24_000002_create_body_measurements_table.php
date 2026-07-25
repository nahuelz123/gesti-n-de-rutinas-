<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('body_measurements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('client_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->date('measured_at');

            $table->decimal('weight', 5, 1)->nullable();      // kg
            $table->decimal('waist', 5, 1)->nullable();       // cm
            $table->decimal('chest', 5, 1)->nullable();       // cm
            $table->decimal('hip', 5, 1)->nullable();         // cm
            $table->decimal('arm', 5, 1)->nullable();         // cm
            $table->decimal('thigh', 5, 1)->nullable();       // cm
            $table->decimal('neck', 5, 1)->nullable();        // cm

            // Calculado y guardado al momento de la carga (método US Navy)
            $table->decimal('body_fat_percentage', 4, 1)->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['client_id', 'measured_at']);
            $table->index(['client_id', 'measured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('body_measurements');
    }
};
