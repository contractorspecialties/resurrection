<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quick_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();

            $table->string('quick_bill_number', 40);
            $table->string('portal_token', 64)->unique();
            $table->string('status', 30)->default('payment_due');

            $table->string('description', 255);
            $table->text('details')->nullable();
            $table->unsignedInteger('amount_cents');

            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'quick_bill_number']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quick_bills');
    }
};
