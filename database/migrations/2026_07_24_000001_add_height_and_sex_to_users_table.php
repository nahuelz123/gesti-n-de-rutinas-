<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('height_cm', 5, 1)->nullable()->after('gym_id');
            $table->enum('sex', ['m', 'f'])->nullable()->after('height_cm');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['height_cm', 'sex']);
        });
    }
};
