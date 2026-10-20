<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_subscriptions', function (Blueprint $table) {
            // NULL means the Stripe environment has not been verified.
            // Existing subscriptions must not be assumed live or test.
            $table->boolean('stripe_livemode')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('company_subscriptions', function (Blueprint $table) {
            $table->dropColumn('stripe_livemode');
        });
    }
};
