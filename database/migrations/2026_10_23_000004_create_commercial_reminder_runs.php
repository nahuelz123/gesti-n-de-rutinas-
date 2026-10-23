<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commercial_reminder_runs', function (Blueprint $table) {
            $table->id();
            $table->date('day');
            $table->foreignId('recipient_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 16);
            $table->unsignedInteger('item_count')->default(0);
            $table->string('error_code', 40)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['day', 'recipient_user_id'], 'commercial_reminder_day_recipient');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_reminder_runs');
    }
};
