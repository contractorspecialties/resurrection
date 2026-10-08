<?php

namespace App\Console\Commands;

use App\Models\CommunicationLog;
use App\Models\Company;
use App\Services\CustomerMessagingService;
use Illuminate\Console\Command;
use Throwable;

class SendJobReminders extends Command
{
    protected $signature = 'jobs:remind';

    protected $description = 'Send morning reminders to contractors with jobs scheduled today';

    public function handle(CustomerMessagingService $messaging): int
    {
        $today = today();

        $companies = Company::query()
            ->where('job_reminder_channel', '!=', 'off')
            ->whereHas('estimates', fn ($query) => $query
                ->where('status', 'active_job')
                ->whereDate('job_date', $today)
            )
            ->with([
                'owner',
                'estimates' => fn ($query) => $query
                    ->where('status', 'active_job')
                    ->whereDate('job_date', $today)
                    ->with('client'),
            ])
            ->get();

        $sent = 0;
        $failed = 0;

        foreach ($companies as $company) {
            $jobCount = $company->estimates->count();
            $channel = $company->job_reminder_channel ?: 'email';

            if (in_array($channel, ['email', 'both'], true)) {
                if (! $this->alreadySent($company->id, 'email')) {
                    try {
                        $messaging->sendJobReminderEmail($company, $jobCount);
                        $sent++;
                    } catch (Throwable $e) {
                        report($e);
                        $failed++;
                        $this->error("Email reminder failed for {$company->name}: {$e->getMessage()}");
                    }
                }
            }

            if (in_array($channel, ['sms', 'both'], true)) {
                if (
                    filled($company->job_reminder_phone)
                    && ! $this->alreadySent($company->id, 'sms')
                ) {
                    try {
                        $messaging->sendJobReminderSms($company, $jobCount);
                        $sent++;
                    } catch (Throwable $e) {
                        report($e);
                        $failed++;
                        $this->error("SMS reminder failed for {$company->name}: {$e->getMessage()}");
                    }
                }
            }
        }

        $this->info("Job reminders complete: {$sent} sent, {$failed} failed.");

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function alreadySent(int $companyId, string $channel): bool
    {
        return CommunicationLog::query()
            ->where('company_id', $companyId)
            ->where('purpose', 'job_reminder')
            ->where('channel', $channel)
            ->where('status', 'sent')
            ->whereDate('created_at', today())
            ->exists();
    }
}
