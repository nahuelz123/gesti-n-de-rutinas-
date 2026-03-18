<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
 public function up(): void
{
    Schema::table('exercises', function (Blueprint $table) {
        $table->foreignId('gym_id')->nullable()->change(); // si ya existe como NOT NULL
        $table->boolean('is_global')->default(false)->index();
        $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();

        $table->index(['gym_id', 'is_global']);
        $table->index(['title']);
    });

    // Regla: si gym_id es NULL => global
    DB::table('exercises')->whereNull('gym_id')->update(['is_global' => true]);
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
