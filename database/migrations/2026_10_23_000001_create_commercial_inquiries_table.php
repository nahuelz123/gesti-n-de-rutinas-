<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('commercial_inquiries', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->string('type', 30)->index();
            $table->string('name', 120);
            $table->string('email', 190);
            $table->string('audience', 30)->nullable();
            $table->string('plan', 120)->nullable();
            $table->text('message')->nullable();
            $table->string('status', 30)->default('new')->index();
            $table->string('privacy_version', 40);
            $table->timestamp('consented_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_inquiries');
    }
};
