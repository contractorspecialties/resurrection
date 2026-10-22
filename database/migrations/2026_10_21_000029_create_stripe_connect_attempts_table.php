<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stripe_connect_attempts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->unique()
                ->constrained('companies')
                ->cascadeOnDelete();

            $table->uuid('request_uuid')->unique();

            $table->string('status', 32)->default('pending');

            $table->string('stripe_account_id')
                ->nullable()
                ->unique();

            $table->string('error_code', 100)->nullable();

            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_connect_attempts');
    }
};
