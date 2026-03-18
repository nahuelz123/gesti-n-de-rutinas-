<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();

            // Multi-gym
            $table->foreignId('gym_id')
                ->constrained()
                ->cascadeOnDelete();

            // Rutina asignada
            $table->foreignId('routine_id')
                ->constrained()
                ->cascadeOnDelete();

            // Cliente al que se le asigna (role=client)
            $table->foreignId('client_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Profe/admin que asignó (si borran el usuario, no querés perder historial)
            $table->foreignId('assigned_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Fechas / historial
            $table->timestamp('assigned_at')->useCurrent(); // cuándo se asignó
            $table->date('start_date')->nullable();         // opcional (si querés “empieza tal día”)
            $table->date('end_date')->nullable();           // NULL = sigue activa

            // Estado (sirve para pause/completed)
            $table->enum('status', ['active', 'paused', 'completed'])->default('active');

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['gym_id', 'status']);
            $table->index(['gym_id', 'client_id']);      // buscar asignaciones de un cliente en un gym
            $table->index(['client_id', 'status']);
            $table->index(['routine_id']);
            $table->index(['client_id', 'end_date']);    // activa = end_date NULL
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
