<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Estimate;
use App\Models\Payment;
use App\Models\QuickBill;
use App\Services\StripeConnectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Stripe\Checkout\Session;
use Stripe\StripeClient;
use Tests\TestCase;

class SubscriptionCheckoutIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function company(): Company
    {
        $company = Company::create([
            'name' => 'Checkout Integration Contractor',
            'slug' => Company::uniqueSlug('Checkout Integration Contractor'),
            'trade' => 'General contracting',
            'city' => 'Test City',
            'state' => 'IN',
            'preferred_customer_contact' => 'email',
        ]);

        $company->forceFill([
            'stripe_account_id' => 'acct_integration_test',
            'stripe_details_submitted' => true,
            'stripe_charges_enabled' => true,
            'stripe_payouts_enabled' => true,
        ])->save();

        return $company;
    }

    private function client(Company $company)
    {
        return $company->clients()->create([
            'name' => 'Test Customer',
            'email' => Str::random(8).'@example.com',
            'phone' => '5555551212',
        ]);
    }

    private function estimate(Company $company): Estimate
    {
        $client = $this->client($company);

        return $company->estimates()->create([
            'client_id' => $client->id,
            'estimate_number' => 'EST-'.Str::upper(Str::random(8)),
            'portal_token' => Str::random(48),
            'status' => 'deposit_due',
            'version' => 1,
            'scope_summary' => 'Checkout integration test',
            'subtotal_cents' => 10000,
            'tax_cents' => 0,
            'total_cents' => 10000,
            'deposit_cents' => 2500,
            'tax_rate' => 0,
            'sent_at' => now(),
        ]);
    }

    private function quickBill(Company $company): QuickBill
    {
        $client = $this->client($company);

        return $company->quickBills()->create([
            'client_id' => $client->id,
            'quick_bill_number' => 'QB-'.Str::upper(Str::random(8)),
            'portal_token' => Str::random(48),
            'status' => 'payment_due',
            'description' => 'Checkout integration test',
            'amount_cents' => 12500,
        ]);
    }

    private function subscribe(Company $company): void
    {
        CompanySubscription::create([
            'company_id' => $company->id,
            'stripe_customer_id' => 'cus_integration_test',
            'stripe_livemode' => false,
            'stripe_subscription_id' => 'sub_integration_test',
            'status' => 'active',
            'price_cents' => 1295,
            'currency' => 'usd',
            'paid_through_at' => now()->addMonth(),
            'cancel_at_period_end' => false,
        ]);
    }

    private function fakeStripe(
        array &$requests,
        bool $allowRetrieve = false
    ): StripeConnectService {
        // A plain mock deliberately avoids the SDK SessionService's
        // real HTTP request implementation.
        $sessions = Mockery::mock();

        $sessions->shouldReceive('create')
            ->andReturnUsing(function ($params, $options) use (&$requests) {
                $requests[] = [
                    'params' => $params,
                    'options' => $options,
                ];

                return Session::constructFrom([
                    'id' => 'cs_test_integration',
                    'object' => 'checkout.session',
                    'status' => 'open',
                    'payment_status' => 'unpaid',
                    'url' => 'https://checkout.stripe.com/test/integration',
                ]);
            });

        if ($allowRetrieve) {
            $sessions->shouldReceive('retrieve')
                ->andReturnUsing(function ($id, $params, $options) {
                    $this->assertSame('cs_test_integration', $id);
                    $this->assertSame('acct_integration_test', $options['stripe_account']);

                    return Session::constructFrom([
                        'id' => 'cs_test_integration',
                        'object' => 'checkout.session',
                        'status' => 'open',
                        'payment_status' => 'unpaid',
                        'url' => 'https://checkout.stripe.com/test/integration',
                    ]);
                });
        }

        $stripe = Mockery::mock(StripeClient::class);

        // StripeClient->__get('checkout') delegates to getService().
        // Return a simple object with a fake sessions dependency.
        $stripe->shouldReceive('getService')
            ->with('checkout')
            ->andReturn((object) [
                'sessions' => $sessions,
            ]);

        $connect = Mockery::mock(StripeConnectService::class)
            ->makePartial();

        $connect->shouldReceive('client')
            ->andReturn($stripe);

        return $connect;
    }

    private function verifyCheckout(
        string $kind,
        bool $monthly
    ): void {
        $company = $this->company();

        if ($monthly) {
            $this->subscribe($company);
        }

        $requests = [];
        $service = $this->fakeStripe($requests);

        if ($kind === 'estimate') {
            $payment = $service->createEstimateCheckout(
                $this->estimate($company),
                'deposit'
            );
            $expectedAmount = 2500;
        } else {
            $payment = $service->createQuickBillCheckout(
                $this->quickBill($company)
            );
            $expectedAmount = 12500;
        }

        $expectedFee = $monthly ? 0 : 200;

        $this->assertSame('pending', $payment->status);
        $this->assertSame($expectedFee, $payment->platform_fee_cents);
        $this->assertSame($expectedFee, $payment->fresh()->platform_fee_cents);
        $this->assertSame($expectedAmount, $payment->amount_cents);
        $this->assertSame('cs_test_integration', $payment->fresh()->provider_checkout_session_id);
        $this->assertCount(1, $requests);

        $params = $requests[0]['params'];
        $options = $requests[0]['options'];

        $this->assertSame('payment', $params['mode']);
        $this->assertSame($expectedAmount, $params['line_items'][0]['price_data']['unit_amount']);
        $this->assertSame('acct_integration_test', $options['stripe_account']);
        $this->assertSame('checkout-payment-'.$payment->id, $options['idempotency_key']);

        if ($monthly) {
            $this->assertArrayNotHasKey(
                'application_fee_amount',
                $params['payment_intent_data']
            );
        } else {
            $this->assertSame(
                200,
                $params['payment_intent_data']['application_fee_amount']
            );
        }
    }

    public function test_payg_estimate_checkout_collects_platform_fee(): void
    {
        $this->verifyCheckout('estimate', false);
    }

    public function test_monthly_estimate_checkout_omits_platform_fee(): void
    {
        $this->verifyCheckout('estimate', true);
    }

    public function test_payg_quick_bill_checkout_collects_platform_fee(): void
    {
        $this->verifyCheckout('quick_bill', false);
    }

    public function test_monthly_quick_bill_checkout_omits_platform_fee(): void
    {
        $this->verifyCheckout('quick_bill', true);
    }

    public function test_existing_payg_checkout_is_not_repriced_after_subscribing(): void
    {
        $company = $this->company();
        $estimate = $this->estimate($company);

        $requests = [];
        $service = $this->fakeStripe($requests, true);

        $original = $service->createEstimateCheckout($estimate, 'deposit');

        $this->assertSame(200, $original->platform_fee_cents);
        $this->subscribe($company);

        $reused = $service->createEstimateCheckout($estimate, 'deposit');

        $this->assertSame($original->id, $reused->id);
        $this->assertSame(200, $reused->fresh()->platform_fee_cents);
        $this->assertSame(1, Payment::count());
        $this->assertCount(1, $requests);
    }
}
