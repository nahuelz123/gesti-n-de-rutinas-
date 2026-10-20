<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->char('endpoint_hash', 64)->unique();
            $table->text('subscription');
            $table->timestamp('last_used_at')->nullable()->index();
            $table->timestamps();
        });
        Schema::create('rest_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('push_subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('sequence');
            $table->timestamp('due_at')->nullable();
            $table->string('status', 16);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamps();
            $table->unique(['push_subscription_id', 'assignment_id']);
            $table->index(['status', 'due_at']);
        });
        Schema::create('web_push_keys', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->text('keys');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rest_alerts');
        Schema::dropIfExists('push_subscriptions');
        Schema::dropIfExists('web_push_keys');
    }
};
