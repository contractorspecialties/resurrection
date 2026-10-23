<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recurring_services', function (Blueprint $table) {
            $table->date('next_visit_on')->nullable();
            $table->time('visit_start_time')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('recurring_services', function (Blueprint $table) {
            $table->dropColumn([
                'next_visit_on',
                'visit_start_time',
            ]);
        });
    }
};
