<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('stripe_account_id')->nullable()->unique()->after('next_estimate_number');
            $table->boolean('stripe_details_submitted')->default(false)->after('stripe_account_id');
            $table->boolean('stripe_charges_enabled')->default(false)->after('stripe_details_submitted');
            $table->boolean('stripe_payouts_enabled')->default(false)->after('stripe_charges_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropUnique(['stripe_account_id']);
            $table->dropColumn([
                'stripe_account_id',
                'stripe_details_submitted',
                'stripe_charges_enabled',
                'stripe_payouts_enabled',
            ]);
        });
    }
};
