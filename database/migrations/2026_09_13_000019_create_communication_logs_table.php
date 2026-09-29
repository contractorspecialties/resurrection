<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('estimate_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quick_bill_id')->nullable()->constrained()->nullOnDelete();

            $table->string('channel', 20); // email | sms
            $table->string('purpose', 40); // estimate | quick_bill
            $table->string('recipient', 190);
            $table->string('subject', 255)->nullable();

            $table->string('status', 30)->default('pending'); // pending | sent | failed
            $table->string('provider', 40)->nullable();
            $table->string('provider_message_id', 190)->nullable();

            $table->text('message');
            $table->text('error_message')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'channel', 'status']);
            $table->index(['estimate_id', 'created_at']);
            $table->index(['quick_bill_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_logs');
    }
};
