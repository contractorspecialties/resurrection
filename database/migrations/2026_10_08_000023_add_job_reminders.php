<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->date('job_date')
                ->nullable()
                ->after('accepted_at');

            $table->index(['company_id', 'status', 'job_date']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->string('job_reminder_phone', 40)
                ->nullable()
                ->after('preferred_customer_contact');

            $table->string('job_reminder_channel', 20)
                ->default('email')
                ->after('job_reminder_phone');
        });
    }

    public function down(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'status', 'job_date']);
            $table->dropColumn('job_date');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'job_reminder_phone',
                'job_reminder_channel',
            ]);
        });
    }
};
