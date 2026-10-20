<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_subscriptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->unique()
                ->constrained()
                ->restrictOnDelete();

            $table->string('stripe_customer_id')->nullable()->unique();
            $table->string('stripe_subscription_id')->nullable()->unique();
            $table->string('pending_checkout_session_id')->nullable()->unique();
            $table->timestamp('pending_checkout_expires_at')->nullable();

            $table->string('status', 40)->default('inactive');
            $table->unsignedInteger('price_cents')->default(1295);
            $table->string('currency', 3)->default('usd');

            $table->timestamp('paid_through_at')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);
            $table->timestamp('revoked_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_subscriptions');
    }
};
