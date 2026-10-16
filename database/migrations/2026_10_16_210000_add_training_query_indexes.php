<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exercise_logs', function (Blueprint $table): void {
            $table->index(
                ['assignment_id', 'routine_day_exercise_id', 'set_number', 'logged_at'],
                'exercise_logs_daily_set_lookup_idx'
            );
        });

        Schema::table('assignments', function (Blueprint $table): void {
            $table->index(
                ['client_id', 'status', 'end_date', 'assigned_at'],
                'assignments_active_client_lookup_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('exercise_logs', function (Blueprint $table): void {
            $table->dropIndex('exercise_logs_daily_set_lookup_idx');
        });

        Schema::table('assignments', function (Blueprint $table): void {
            $table->dropIndex('assignments_active_client_lookup_idx');
        });
    }
};
