<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->date('performed_on');
            $table->string('activity_key', 60);
            $table->string('activity_label', 160);
            $table->string('category', 20);
            $table->unsignedSmallInteger('duration_minutes');
            $table->decimal('body_weight', 6, 2);
            $table->decimal('met', 5, 2);
            $table->string('source_code', 10);
            $table->decimal('active_kcal', 10, 2);
            $table->string('calculation_version', 40);
            $table->uuid('request_token');
            $table->string('notes', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['client_id', 'request_token']);
            $table->index(['client_id', 'performed_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
