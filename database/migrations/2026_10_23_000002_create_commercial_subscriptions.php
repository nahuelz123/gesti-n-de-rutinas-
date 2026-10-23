<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gyms', function (Blueprint $table) {
            $table->boolean('billing_managed')->default(false);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('commercial_access_enabled')->default(true);
        });
        Schema::create('commercial_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('gym_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->string('kind', 20)->default('software');
            $table->string('plan_code', 50);
            $table->unsignedInteger('amount_cents');
            $table->string('currency', 3)->default('ARS');
            $table->string('status', 30)->default('trialing');
            $table->string('provider_id')->nullable()->unique();
            $table->text('checkout_url')->nullable();
            $table->string('provider_status', 30)->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('paid_until')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);
            $table->timestamp('renewal_consented_at')->nullable();
            $table->string('terms_version', 40);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
        Schema::create('commercial_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commercial_subscription_id')->constrained()->cascadeOnDelete();
            $table->string('provider_payment_id')->unique();
            $table->timestamp('provider_updated_at', 6)->nullable();
            $table->string('status', 30);
            $table->unsignedInteger('amount_cents');
            $table->string('currency', 3);
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_payments');
        Schema::dropIfExists('commercial_subscriptions');
        Schema::table('gyms', function (Blueprint $table) {
            $table->dropColumn('billing_managed');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('commercial_access_enabled');
        });
    }
};
