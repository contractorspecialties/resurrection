<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('quick_bill_id')
                ->nullable()
                ->after('estimate_id')
                ->constrained('quick_bills')
                ->nullOnDelete();

            $table->index(['quick_bill_id', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['quick_bill_id']);
            $table->dropIndex(['quick_bill_id', 'purpose']);
            $table->dropColumn('quick_bill_id');
        });
    }
};
