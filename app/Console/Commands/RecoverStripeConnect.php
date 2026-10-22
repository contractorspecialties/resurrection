<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\StripeConnectService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class RecoverStripeConnect extends Command
{
    protected $signature = 'stripe:connect-recover
        {company : Local company ID}
        {account : Stripe connected account ID}';

    protected $description = 'Safely recover an interrupted Stripe Connect account creation';

    public function handle(StripeConnectService $stripe): int
    {
        $companyId = (string) $this->argument('company');
        $accountId = (string) $this->argument('account');

        if (! ctype_digit($companyId) || (int) $companyId < 1
            || ! preg_match('/^acct_[A-Za-z0-9]+$/', $accountId)) {
            $this->error('Invalid company ID or Stripe account ID.');
            return self::FAILURE;
        }

        $company = Company::find($companyId);

        if (! $company || filled($company->stripe_account_id)) {
            $this->error('Company missing or already has a Stripe account.');
            return self::FAILURE;
        }

        $attempt = DB::table('stripe_connect_attempts')
            ->where('company_id', $company->id)
            ->first();

        if (! $attempt || $attempt->status !== 'pending'
            || filled($attempt->stripe_account_id)) {
            $this->error('No eligible pending Connect attempt.');
            return self::FAILURE;
        }

        if (Company::where('stripe_account_id', $accountId)->exists()
            || DB::table('stripe_connect_attempts')
                ->where('stripe_account_id', $accountId)->exists()) {
            $this->error('Stripe account is already assigned.');
            return self::FAILURE;
        }

        try {
            $remote = $stripe->client()->accounts->retrieve($accountId, []);
            $remoteData = $remote->toArray();

            $remoteCompany = data_get($remoteData, 'metadata.cs_company_id');
            $remoteRequest = data_get($remoteData, 'metadata.cs_request_uuid');

            if ((string) $remote->id !== $accountId
                || (string) $remoteCompany !== (string) $company->id
                || (string) $remoteRequest !== (string) $attempt->request_uuid) {
                $this->error('Stripe metadata does not match this pending attempt.');
                return self::FAILURE;
            }

            $this->line('Company ID: '.$company->id);
            $this->line('Stripe account: '.$accountId);
            $this->line('Request UUID: '.$attempt->request_uuid);
            $this->line('Stripe identity: verified');

            if (! $this->confirm(
                'Attach this verified account to the company?',
                false
            )) {
                $this->warn('Recovery cancelled. Nothing changed.');
                return self::FAILURE;
            }

            DB::transaction(function () use (
                $company,
                $accountId,
                $attempt
            ): void {
                $locked = Company::query()
                    ->lockForUpdate()
                    ->findOrFail($company->id);

                $pending = DB::table('stripe_connect_attempts')
                    ->where('company_id', $locked->id)
                    ->lockForUpdate()
                    ->first();

                if (filled($locked->stripe_account_id)
                    || ! $pending
                    || $pending->status !== 'pending'
                    || filled($pending->stripe_account_id)
                    || $pending->request_uuid !== $attempt->request_uuid
                    || Company::where('stripe_account_id', $accountId)
                        ->exists()
                    || DB::table('stripe_connect_attempts')
                        ->where('stripe_account_id', $accountId)
                        ->exists()) {
                    throw new \RuntimeException(
                        'Recovery state changed. No account attached.'
                    );
                }

                $locked->update([
                    'stripe_account_id' => $accountId,
                ]);

                DB::table('stripe_connect_attempts')
                    ->where('id', $pending->id)
                    ->update([
                        'stripe_account_id' => $accountId,
                        'status' => 'completed',
                        'updated_at' => now(),
                    ]);
            });

            $this->info('Stripe account recovered successfully.');
            $this->line('Refresh Stripe status through payment settings.');
            return self::SUCCESS;
        } catch (Throwable $e) {
            report($e);
            $this->error(
                'Recovery failed or could not be verified. '
                .'No further recovery action should be attempted '
                .'without checking the current database state.'
            );
            return self::FAILURE;
        }
    }
}
