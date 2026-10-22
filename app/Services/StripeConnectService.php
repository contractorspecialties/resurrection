<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Estimate;
use App\Models\Payment;
use App\Models\QuickBill;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Stripe\StripeClient;

class StripeConnectService
{
    private const NORMAL_FEE_CENTS = 200;
    private const PROJECT_FEE_CAP_CENTS = 500;

    public function client(): StripeClient
    {
        $secret = config('services.stripe.secret');

        abort_unless(filled($secret), 500, 'Stripe is not configured.');

        return new StripeClient($secret);
    }

    public function createOrRefreshConnectedAccount(Company $company, string $email): Company
    {
        /*
         * Reserve one durable creation attempt before making any
         * external Stripe request. Never hold a database transaction
         * open during the Stripe API call.
         */
        $requestUuid = DB::transaction(function () use ($company): ?string {
            $locked = Company::query()
                ->lockForUpdate()
                ->findOrFail($company->id);

            if (filled($locked->stripe_account_id)) {
                return null;
            }

            $existing = DB::table('stripe_connect_attempts')
                ->where('company_id', $locked->id)
                ->first();

            if ($existing) {
                abort(
                    409,
                    'Stripe setup was already started for this company. '
                    .'Please contact support if setup did not finish.'
                );
            }

            $uuid = (string) \Illuminate\Support\Str::uuid();

            DB::table('stripe_connect_attempts')->insert([
                'company_id' => $locked->id,
                'request_uuid' => $uuid,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $uuid;
        });

        /*
         * Another request might have already completed creation.
         * In that case, only refresh the existing account.
         */
        if ($requestUuid === null) {
            return $this->refreshAccountStatus($company->refresh());
        }

        try {
            $account = $this->client()->accounts->create([
                'type' => 'standard',
                'country' => 'US',
                'email' => $email,
                'metadata' => [
                    'cs_company_id' => (string) $company->id,
                    'cs_request_uuid' => $requestUuid,
                ],
            ], [
                'idempotency_key' => 'cs-connect-'.$requestUuid,
            ]);
        } catch (\Throwable $e) {
            /*
             * A failed Stripe request may have an uncertain outcome.
             * Preserve the pending attempt for reconciliation.
             */
            report($e);

            abort(
                503,
                'Stripe setup could not be completed. '
                .'Please contact support before trying again.'
            );
        }

        $accountId = (string) ($account->id ?? '');

        if (! preg_match('/^acct_[A-Za-z0-9]+$/', $accountId)) {
            report(new \RuntimeException(
                'Stripe Connect returned an invalid account identifier.'
            ));

            abort(
                503,
                'Stripe setup needs assistance. Please contact support.'
            );
        }

        /*
         * Save the account ID and mark the attempt completed in
         * the same database transaction. If this fails, the pending
         * attempt remains as a reconciliation safeguard.
         */
        DB::transaction(function () use ($company, $requestUuid, $accountId): void {
            $locked = Company::query()
                ->lockForUpdate()
                ->findOrFail($company->id);

            $attempt = DB::table('stripe_connect_attempts')
                ->where('company_id', $locked->id)
                ->lockForUpdate()
                ->first();

            if (! $attempt
                || $attempt->request_uuid !== $requestUuid
                || $attempt->status !== 'pending'
                || filled($locked->stripe_account_id)) {
                throw new \RuntimeException(
                    'Stripe Connect attempt state requires reconciliation.'
                );
            }

            $locked->update([
                'stripe_account_id' => $accountId,
            ]);

            DB::table('stripe_connect_attempts')
                ->where('id', $attempt->id)
                ->update([
                    'stripe_account_id' => $accountId,
                    'status' => 'completed',
                    'updated_at' => now(),
                ]);
        });

        return $this->refreshAccountStatus($company->refresh());
    }

    public function refreshAccountStatus(Company $company): Company
    {
        if (! $company->stripe_account_id) {
            return $company;
        }

        $account = $this->client()->accounts->retrieve($company->stripe_account_id, []);

        $company->update([
            'stripe_details_submitted' => (bool) $account->details_submitted,
            'stripe_charges_enabled' => (bool) $account->charges_enabled,
            'stripe_payouts_enabled' => (bool) $account->payouts_enabled,
        ]);

        return $company->refresh();
    }

    public function onboardingUrl(Company $company): string
    {
        $link = $this->client()->accountLinks->create([
            'account' => $company->stripe_account_id,
            'refresh_url' => route('payments.stripe.refresh'),
            'return_url' => route('payments.stripe.return'),
            'type' => 'account_onboarding',
        ]);

        return $link->url;
    }

    public function createEstimateCheckout(Estimate $estimate, string $purpose): Payment
    {
        abort_unless(
            in_array($purpose, ['deposit', 'final'], true),
            422,
            'Unsupported estimate payment purpose.'
        );

        return DB::transaction(function () use ($estimate, $purpose) {
            $estimate = Estimate::query()
                ->with(['company', 'client'])
                ->lockForUpdate()
                ->findOrFail($estimate->id);

            $expectedStatus = $purpose === 'deposit'
                ? 'deposit_due'
                : 'balance_due';

            abort_unless(
                $estimate->status === $expectedStatus,
                409,
                'This payment is not currently due.'
            );

            abort_unless(
                $estimate->company->hasHealthyStripeConnection(),
                409,
                'Online payments are not ready for this contractor.'
            );

            $amountDue = $purpose === 'deposit'
                ? max(0, $estimate->deposit_cents - $estimate->depositPaidCents())
                : $estimate->balanceDueCents();

            abort_if(
                $amountDue <= 0,
                409,
                'This payment has already been satisfied.'
            );

            $activeCheckoutKey = "estimate:{$estimate->id}:{$purpose}";

            if ($existing = $this->reusableCheckout(
                $estimate->company,
                $activeCheckoutKey,
                $amountDue
            )) {
                return $existing;
            }

            return $this->createCheckout(
                company: $estimate->company,
                clientEmail: $estimate->client->email,
                amountCents: $amountDue,
                feeCents: $this->estimateFeeCents($estimate, $amountDue),
                purpose: $purpose,
                description: $purpose === 'deposit'
                    ? "Deposit for {$estimate->estimate_number}"
                    : "Final balance for {$estimate->estimate_number}",
                successUrl: route('portal.payment.return', $estimate->portal_token)
                    . '?session_id={CHECKOUT_SESSION_ID}',
                cancelUrl: route('portal.show', $estimate->portal_token),
                paymentAttributes: [
                    'client_id' => $estimate->client_id,
                    'estimate_id' => $estimate->id,
                ],
                metadata: [
                    'estimate_id' => (string) $estimate->id,
                    'estimate_number' => $estimate->estimate_number,
                    'estimate_version' => $estimate->version,
                    'project_fee_cap_cents' => self::PROJECT_FEE_CAP_CENTS,
                ],
                activeCheckoutKey: $activeCheckoutKey,
            );
        });
    }

    public function createQuickBillCheckout(QuickBill $quickBill): Payment
    {
        return DB::transaction(function () use ($quickBill) {
            $quickBill = QuickBill::query()
                ->with(['company', 'client'])
                ->lockForUpdate()
                ->findOrFail($quickBill->id);

            abort_unless(
                $quickBill->status === 'payment_due',
                409,
                'This Quick Bill is not currently due.'
            );

            abort_unless(
                $quickBill->company->hasHealthyStripeConnection(),
                409,
                'Online payments are not ready for this contractor.'
            );

            $amountDue = $quickBill->balanceDueCents();

            abort_if(
                $amountDue <= 0,
                409,
                'This Quick Bill has already been paid.'
            );

            $activeCheckoutKey = "quick_bill:{$quickBill->id}";

            if ($existing = $this->reusableCheckout(
                $quickBill->company,
                $activeCheckoutKey,
                $amountDue
            )) {
                return $existing;
            }

            return $this->createCheckout(
                company: $quickBill->company,
                clientEmail: $quickBill->client->email,
                amountCents: $amountDue,
                feeCents: $this->quickBillFeeCents($quickBill, $amountDue),
                purpose: 'quick_bill',
                description: "{$quickBill->quick_bill_number}: {$quickBill->description}",
                successUrl: route(
                    'portal.quick-bill.return',
                    $quickBill->portal_token
                ).'?session_id={CHECKOUT_SESSION_ID}',
                cancelUrl: route(
                    'portal.quick-bill.show',
                    $quickBill->portal_token
                ),
                paymentAttributes: [
                    'client_id' => $quickBill->client_id,
                    'quick_bill_id' => $quickBill->id,
                ],
                metadata: [
                    'quick_bill_id' => (string) $quickBill->id,
                    'quick_bill_number' => $quickBill->quick_bill_number,
                ],
                activeCheckoutKey: $activeCheckoutKey,
            );
        });
    }

    public function retireActiveCheckout(
        Company $company,
        string $activeCheckoutKey,
        string $reason = 'superseded'
    ): void {
        $payment = Payment::query()
            ->where('provider', 'stripe')
            ->where('status', 'pending')
            ->where('active_checkout_key', $activeCheckoutKey)
            ->first();

        if (! $payment) {
            return;
        }

        if (! $payment->provider_checkout_session_id) {
            abort(
                409,
                'A payment checkout is currently being prepared. Please try again.'
            );
        }

        $session = $this->client()->checkout->sessions->retrieve(
            $payment->provider_checkout_session_id,
            [],
            ['stripe_account' => $company->stripe_account_id]
        );

        if (($session->payment_status ?? null) === 'paid') {
            abort(
                409,
                'An online payment has already been submitted and is being processed.'
            );
        }

        if (($session->status ?? null) === 'open') {
            $this->client()->checkout->sessions->expire(
                $payment->provider_checkout_session_id,
                [],
                ['stripe_account' => $company->stripe_account_id]
            );
        } elseif (($session->status ?? null) !== 'expired') {
            abort(
                409,
                'An online payment is currently being processed.'
            );
        }

        $payment->update([
            'status' => 'failed',
            'active_checkout_key' => null,
            'failed_at' => now(),
            'metadata' => array_merge($payment->metadata ?? [], [
                'checkout_closed_reason' => $reason,
            ]),
        ]);
    }

    private function reusableCheckout(
        Company $company,
        string $activeCheckoutKey,
        int $expectedAmountCents
    ): ?Payment {
        $payment = Payment::query()
            ->where('provider', 'stripe')
            ->where('status', 'pending')
            ->where('active_checkout_key', $activeCheckoutKey)
            ->first();

        if (! $payment) {
            return null;
        }

        if (! $payment->provider_checkout_session_id) {
            abort(
                409,
                'A payment checkout is already being prepared. Please try again.'
            );
        }

        $session = $this->client()->checkout->sessions->retrieve(
            $payment->provider_checkout_session_id,
            [],
            ['stripe_account' => $company->stripe_account_id]
        );

        if (($session->payment_status ?? null) === 'paid') {
            abort(
                409,
                'This payment has already been submitted and is being processed.'
            );
        }

        if (($session->status ?? null) === 'open') {
            if ($payment->amount_cents === $expectedAmountCents) {
                $payment->setAttribute('checkout_url', $session->url);

                return $payment;
            }

            $this->client()->checkout->sessions->expire(
                $payment->provider_checkout_session_id,
                [],
                ['stripe_account' => $company->stripe_account_id]
            );

            $payment->update([
                'status' => 'failed',
                'active_checkout_key' => null,
                'failed_at' => now(),
                'metadata' => array_merge($payment->metadata ?? [], [
                    'checkout_closed_reason' => 'amount_changed',
                ]),
            ]);

            return null;
        }

        if (($session->status ?? null) === 'expired') {
            $payment->update([
                'status' => 'failed',
                'active_checkout_key' => null,
                'failed_at' => now(),
                'metadata' => array_merge($payment->metadata ?? [], [
                    'checkout_closed_reason' => 'expired',
                ]),
            ]);

            return null;
        }

        abort(
            409,
            'This payment has already been submitted and is being processed.'
        );
    }

    private function estimateFeeCents(Estimate $estimate, int $amountCents): int
    {
        $alreadyCollected = (int) Payment::query()
            ->where('estimate_id', $estimate->id)
            ->whereIn('status', ['paid', 'refunded'])
            ->sum('platform_fee_cents');

        $remainingCap = max(0, self::PROJECT_FEE_CAP_CENTS - $alreadyCollected);

        return app(SubscriptionEntitlementService::class)
            ->platformFeeCents(
                $estimate->company,
                min(
                    self::NORMAL_FEE_CENTS,
                    $remainingCap,
                    max(0, $amountCents - 1)
                )
            );
    }

    private function quickBillFeeCents(QuickBill $quickBill, int $amountCents): int
    {
        return app(SubscriptionEntitlementService::class)
            ->platformFeeCents(
                $quickBill->company,
                min(self::NORMAL_FEE_CENTS, max(0, $amountCents - 1))
            );
    }

    private function createCheckout(
        Company $company,
        ?string $clientEmail,
        int $amountCents,
        int $feeCents,
        string $purpose,
        string $description,
        string $successUrl,
        string $cancelUrl,
        array $paymentAttributes,
        array $metadata,
        string $activeCheckoutKey,
    ): Payment {
        try {
            $payment = Payment::create(array_merge([
                'company_id' => $company->id,
                'provider' => 'stripe',
                'purpose' => $purpose,
                'status' => 'pending',
                'active_checkout_key' => $activeCheckoutKey,
                'amount_cents' => $amountCents,
                'platform_fee_cents' => $feeCents,
                'currency' => 'usd',
                'metadata' => $metadata,
            ], $paymentAttributes));
        } catch (\Illuminate\Database\QueryException $e) {
            $existing = Payment::query()
                ->where('provider', 'stripe')
                ->where('status', 'pending')
                ->where('active_checkout_key', $activeCheckoutKey)
                ->first();

            if ($existing) {
                $existing = $this->reusableCheckout(
                    $company,
                    $activeCheckoutKey,
                    $amountCents
                );

                if ($existing) {
                    return $existing;
                }
            }

            throw $e;
        }

        try {
            $intentData = [
                'metadata' => array_merge($metadata, [
                    'payment_id' => (string) $payment->id,
                    'purpose' => $purpose,
                ]),
            ];

            if ($feeCents > 0) {
                $intentData['application_fee_amount'] = $feeCents;
            }

            $session = $this->client()->checkout->sessions->create([
                'mode' => 'payment',
                'client_reference_id' => (string) $payment->id,
                'customer_email' => $clientEmail ?: null,
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => $description,
                            'description' => $company->name,
                        ],
                        'unit_amount' => $amountCents,
                    ],
                    'quantity' => 1,
                ]],
                'payment_intent_data' => $intentData,
                'metadata' => array_merge($metadata, [
                    'payment_id' => (string) $payment->id,
                    'company_id' => (string) $company->id,
                    'purpose' => $purpose,
                ]),
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
            ], [
                'stripe_account' => $company->stripe_account_id,
                'idempotency_key' => 'checkout-payment-'.$payment->id,
            ]);

            $payment->update([
                'provider_checkout_session_id' => $session->id,
            ]);

            $payment->setAttribute('checkout_url', $session->url);

            return $payment;
        } catch (\Throwable $e) {
            $payment->update([
                'status' => 'failed',
                'active_checkout_key' => null,
                'failed_at' => now(),
                'metadata' => array_merge($payment->metadata ?? [], [
                    'checkout_error' => $e->getMessage(),
                ]),
            ]);

            throw $e;
        }
    }
}
