<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('scope', 64);
            $table->string('version', 64);
            $table->timestamp('granted_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'scope', 'version', 'revoked_at'], 'user_consents_lookup');
        });

        Schema::table('gyms', function (Blueprint $table) {
            $table->string('privacy_contact_name')->nullable();
            $table->string('privacy_contact_email')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('gyms', function (Blueprint $table) {
            $table->dropColumn(['privacy_contact_name', 'privacy_contact_email']);
        });

        Schema::dropIfExists('user_consents');
    }
};
