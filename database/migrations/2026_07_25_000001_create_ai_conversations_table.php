<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();

            // Dueño de la conversación: el cliente (chat propio) o el coach (asistente).
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Solo se usa en el asistente del coach: sobre qué cliente está preguntando.
            // Null = chat propio del cliente.
            $table->foreignId('client_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete();

            $table->enum('role', ['user', 'assistant']);
            $table->text('content');

            $table->timestamps();

            $table->index(['user_id', 'client_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_conversations');
    }
};
