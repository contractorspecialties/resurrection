<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Estimate;
use App\Models\Payment;
use App\Models\QuickBill;
use Illuminate\Support\Facades\Http;
use Stripe\StripeClient;

class StripeConnectService
{
    private const ACCOUNTS_V2_VERSION = '2026-08-26.preview';
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
        if (! $company->stripe_account_id) {
            $response = Http::withToken(config('services.stripe.secret'))
                ->withHeaders([
                    'Stripe-Version' => self::ACCOUNTS_V2_VERSION,
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.stripe.com/v2/core/accounts', [
                    'contact_email' => $email,
                    'display_name' => $company->name,
                    'identity' => [
                        'country' => 'us',
                        'entity_type' => 'company',
                        'business_details' => [
                            'registered_name' => $company->name,
                        ],
                    ],
                    'dashboard' => 'full',
                    'configuration' => [
                        'merchant' => [
                            'capabilities' => [
                                'card_payments' => ['requested' => true],
                            ],
                        ],
                    ],
                    'defaults' => [
                        'currency' => 'usd',
                        'responsibilities' => [
                            'fees_collector' => 'stripe',
                            'losses_collector' => 'stripe',
                        ],
                        'locales' => ['en-US'],
                    ],
                    'include' => [
                        'configuration.merchant',
                        'requirements',
                    ],
                ]);

            if (! $response->successful()) {
                abort($response->status(), 'Stripe account creation failed: '.$response->body());
            }

            $accountId = data_get($response->json(), 'id');

            abort_unless(
                filled($accountId),
                500,
                'Stripe created an account but returned no account ID.'
            );

            $company->update([
                'stripe_account_id' => $accountId,
            ]);
        }

        return $this->refreshAccountStatus($company);
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
        $estimate->loadMissing(['company', 'client']);

        abort_unless(
            in_array($purpose, ['deposit', 'final'], true),
            422,
            'Unsupported estimate payment purpose.'
        );

        $expectedStatus = $purpose === 'deposit' ? 'deposit_due' : 'balance_due';

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

        abort_if($amountDue <= 0, 409, 'This payment has already been satisfied.');

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
        );
    }

    public function createQuickBillCheckout(QuickBill $quickBill): Payment
    {
        $quickBill->loadMissing(['company', 'client']);

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

        abort_if($amountDue <= 0, 409, 'This Quick Bill has already been paid.');

        return $this->createCheckout(
            company: $quickBill->company,
            clientEmail: $quickBill->client->email,
            amountCents: $amountDue,
            feeCents: min(self::NORMAL_FEE_CENTS, max(0, $amountDue - 1)),
            purpose: 'quick_bill',
            description: "{$quickBill->quick_bill_number}: {$quickBill->description}",
            successUrl: route('portal.quick-bill.return', $quickBill->portal_token)
                . '?session_id={CHECKOUT_SESSION_ID}',
            cancelUrl: route('portal.quick-bill.show', $quickBill->portal_token),
            paymentAttributes: [
                'client_id' => $quickBill->client_id,
                'quick_bill_id' => $quickBill->id,
            ],
            metadata: [
                'quick_bill_id' => (string) $quickBill->id,
                'quick_bill_number' => $quickBill->quick_bill_number,
            ],
        );
    }

    private function estimateFeeCents(Estimate $estimate, int $amountCents): int
    {
        $alreadyCollected = (int) Payment::query()
            ->where('estimate_id', $estimate->id)
            ->where('status', 'paid')
            ->sum('platform_fee_cents');

        $remainingCap = max(0, self::PROJECT_FEE_CAP_CENTS - $alreadyCollected);

        return min(
            self::NORMAL_FEE_CENTS,
            $remainingCap,
            max(0, $amountCents - 1)
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
    ): Payment {
        $payment = Payment::create(array_merge([
            'company_id' => $company->id,
            'provider' => 'stripe',
            'purpose' => $purpose,
            'status' => 'pending',
            'amount_cents' => $amountCents,
            'platform_fee_cents' => $feeCents,
            'currency' => 'usd',
            'metadata' => $metadata,
        ], $paymentAttributes));

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
            ]);

            $payment->update([
                'provider_checkout_session_id' => $session->id,
            ]);

            $payment->setAttribute('checkout_url', $session->url);

            return $payment;
        } catch (\Throwable $e) {
            $payment->update([
                'status' => 'failed',
                'failed_at' => now(),
                'metadata' => array_merge($payment->metadata ?? [], [
                    'checkout_error' => $e->getMessage(),
                ]),
            ]);

            throw $e;
        }
    }
}
