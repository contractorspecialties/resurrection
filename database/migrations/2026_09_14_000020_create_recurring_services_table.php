<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('recurring_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignId('price_book_item_id')->nullable()->constrained('price_book_items')->nullOnDelete();
            $table->string('name',180);
            $table->text('description')->nullable();
            $table->unsignedInteger('amount_cents');
            $table->string('frequency',30);
            $table->date('next_bill_on');
            $table->boolean('auto_send_email')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_generated_at')->nullable();
            $table->timestamps();
            $table->index(['company_id','is_active','next_bill_on']);
        });
    }
    public function down(): void { Schema::dropIfExists('recurring_services'); }
};
