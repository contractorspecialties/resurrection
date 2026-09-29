<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estimate_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estimate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();

            $table->unsignedInteger('estimate_version');
            $table->string('signature_name', 160);
            $table->timestamp('accepted_at');

            $table->json('snapshot');
            $table->string('ip_address', 64)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            $table->unique(['estimate_id', 'estimate_version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estimate_acceptances');
    }
};
