<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commercial_subscriptions', function (Blueprint $table) {
            $table->string('billing_mode', 20)->default('manual');
            $table->string('next_plan_code', 50)->nullable();
            $table->unsignedInteger('next_amount_cents')->nullable();
            $table->timestamp('next_plan_effective_at')->nullable();
        });
        DB::table('commercial_subscriptions')->whereNotNull('provider_id')->update(['billing_mode' => 'mercadopago']);
        Schema::table('commercial_payments', function (Blueprint $table) {
            $table->uuid('public_id')->nullable()->unique();
            $table->string('source', 20)->default('mercadopago');
            $table->string('method', 30)->nullable();
            $table->string('reference', 120)->nullable();
            $table->string('reference_key', 64)->nullable()->unique();
            $table->timestamp('paid_at')->nullable();
            $table->unsignedInteger('refunded_cents')->default(0);
            $table->string('fiscal_reference', 120)->nullable();
            $table->string('plan_code', 50)->nullable();
            $table->index(['commercial_subscription_id', 'status', 'period_start', 'period_end'], 'commercial_payment_coverage');
            $table->index(['source', 'paid_at']);
        });
        DB::table('commercial_payments')->orderBy('id')->eachById(function ($payment) {
            DB::table('commercial_payments')->where('id', $payment->id)->update(['public_id' => (string) Str::uuid()]);
        });
        Schema::create('commercial_refunds', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('commercial_payment_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('amount_cents');
            $table->string('reason', 500);
            $table->date('performed_on');
            $table->timestamps();
            $table->index('performed_on');
        });
        Schema::create('commercial_operations', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('commercial_subscription_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name', 255);
            $table->string('action', 40);
            $table->string('request_hash', 64);
            $table->json('payload');
            $table->json('result');
            $table->timestamps();
            $table->index(['commercial_subscription_id', 'id']);
        });
        Schema::create('commercial_settings', function (Blueprint $table) {
            $table->id();
            $table->json('values');
            $table->timestamps();
        });
        DB::table('commercial_settings')->insert(['id' => 1, 'values' => '{}', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_settings');
        Schema::dropIfExists('commercial_operations');
        Schema::dropIfExists('commercial_refunds');
        Schema::table('commercial_payments', function (Blueprint $table) {
            $table->dropIndex('commercial_payment_coverage');
            $table->dropIndex(['source', 'paid_at']);
            $table->dropColumn(['public_id', 'source', 'method', 'reference', 'reference_key', 'paid_at', 'refunded_cents', 'fiscal_reference', 'plan_code']);
        });
        Schema::table('commercial_subscriptions', fn (Blueprint $table) => $table->dropColumn(['billing_mode', 'next_plan_code', 'next_amount_cents', 'next_plan_effective_at']));
    }
};
