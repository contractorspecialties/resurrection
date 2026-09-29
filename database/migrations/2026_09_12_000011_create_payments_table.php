<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('estimate_id')->nullable()->constrained()->nullOnDelete();

            $table->string('provider', 30);
            $table->string('purpose', 30);
            $table->string('status', 30)->default('pending');

            $table->unsignedInteger('amount_cents');
            $table->unsignedInteger('platform_fee_cents')->default(0);
            $table->string('currency', 3)->default('usd');

            $table->string('provider_checkout_session_id')->nullable();
            $table->string('provider_payment_intent_id')->nullable();
            $table->string('provider_charge_id')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('refunded_at')->nullable();

            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_checkout_session_id']);
            $table->index(['company_id', 'status']);
            $table->index(['estimate_id', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
