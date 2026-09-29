<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->string('portal_token', 64)
                ->nullable()
                ->unique()
                ->after('estimate_number');

            $table->unsignedInteger('version')
                ->default(1)
                ->after('status');

            $table->timestamp('accepted_at')
                ->nullable()
                ->after('sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->dropUnique(['portal_token']);
            $table->dropColumn(['portal_token', 'version', 'accepted_at']);
        });
    }
};
