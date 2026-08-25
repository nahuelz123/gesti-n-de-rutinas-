<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('recipes', function (Blueprint $table) {
            $table->foreignId('gym_id')->nullable()->constrained()->cascadeOnDelete();
            $table->index(['gym_id']);
        });
    }

    public function down(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('recipes', function (Blueprint $table) {
            $table->dropForeign(['gym_id']);
            $table->dropColumn('gym_id');
        });
    }
};
