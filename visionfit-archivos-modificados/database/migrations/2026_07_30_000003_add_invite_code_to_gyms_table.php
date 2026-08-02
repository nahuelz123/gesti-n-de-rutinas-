<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\Gym;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gyms', function (Blueprint $table) {
            $table->string('invite_code', 12)->nullable()->unique()->after('id');
        });

        // Genera un código para los gimnasios que ya existan.
        Gym::whereNull('invite_code')->get()->each(function (Gym $gym) {
            $gym->update(['invite_code' => strtoupper(Str::random(8))]);
        });
    }

    public function down(): void
    {
        Schema::table('gyms', function (Blueprint $table) {
            $table->dropColumn('invite_code');
        });
    }
};
