<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estimates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();

            $table->string('estimate_number', 40);
            $table->string('status', 40)->default('draft');

            $table->text('scope_summary')->nullable();
            $table->text('notes')->nullable();

            $table->unsignedInteger('subtotal_cents')->default(0);
            $table->unsignedInteger('tax_cents')->default(0);
            $table->unsignedInteger('total_cents')->default(0);
            $table->unsignedInteger('deposit_cents')->default(0);

            $table->decimal('tax_rate', 6, 3)->default(0);
            $table->timestamp('sent_at')->nullable();

            $table->timestamps();

            $table->unique(['company_id', 'estimate_number']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estimates');
    }
};
