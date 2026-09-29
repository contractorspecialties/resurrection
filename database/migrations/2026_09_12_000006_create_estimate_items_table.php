<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estimate_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estimate_id')->constrained()->cascadeOnDelete();

            $table->string('description', 255);
            $table->text('details')->nullable();

            $table->decimal('quantity', 10, 2)->default(1);
            $table->unsignedInteger('unit_price_cents')->default(0);
            $table->unsignedInteger('line_total_cents')->default(0);
            $table->boolean('is_taxable')->default(true);
            $table->unsignedInteger('position')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estimate_items');
    }
};
