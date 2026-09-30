<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('active_checkout_key')
                ->nullable()
                ->after('status');

            $table->unique('active_checkout_key');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['active_checkout_key']);
            $table->dropColumn('active_checkout_key');
        });
    }
};
