<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\SubscriptionEvent;
use App\Services\StripeConnectService;
use App\Services\SubscriptionEntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Stripe\StripeClient;
use Tests\TestCase;

class SubscriptionPaidWebhookIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'whsec_paid_webhook_integration';

    private function company(): Company
    {
        return Company::create([
            'name' => 'Webhook Integration Contractor',
            'slug' => Company::uniqueSlug('Webhook Integration Contractor'),
            'trade' => 'General contracting',
            'city' => 'Test City',
            'state' => 'IN',
            'preferred_customer_contact' => 'email',
        ]);
    }

    private function object(array $data): object
    {
        return json_decode(
            json_encode($data, JSON_THROW_ON_ERROR),
            false,
            512,
            JSON_THROW_ON_ERROR
        );
    }

    private function fakeStripe(
        Company $company,
        int $periodEnd,
        bool $validPrice = true,
        bool $invoiceLive = false,
        bool $subscriptionLive = false
    ): void {
        $priceId = 'price_monthly_integration';
        $actualPrice = $validPrice ? $priceId : 'price_unrelated';

        $invoice = $this->object([
            'id' => 'in_webhook_integration',
            'status' => 'paid',
            'livemode' => $invoiceLive,
            'amount_paid' => 1295,
            'currency' => 'usd',
            'customer' => 'cus_webhook_integration',
            'parent' => [
                'subscription_details' => [
                    'subscription' => 'sub_webhook_integration',
                ],
            ],
        ]);

        $subscription = $this->object([
            'id' => 'sub_webhook_integration',
            'customer' => 'cus_webhook_integration',
            'livemode' => $subscriptionLive,
            'status' => 'active',
            'cancel_at_period_end' => false,
            'metadata' => [
                'company_id' => (string) $company->id,
            ],
            'items' => [
                'data' => [[
                    'id' => 'si_webhook_integration',
                    'quantity' => 1,
                    'price' => [
                        'id' => $priceId,
                        'unit_amount' => 1295,
                        'currency' => 'usd',
                        'recurring' => [
                            'interval' => 'month',
                            'interval_count' => 1,
                        ],
                    ],
                ]],
            ],
        ]);

        $lines = $this->object([
            'has_more' => false,
            'data' => [[
                'parent' => [
                    'subscription_item_details' => [
                        'subscription_item' => 'si_webhook_integration',
                        'proration' => false,
                    ],
                ],
                'pricing' => [
                    'price_details' => [
                        'price' => $actualPrice,
                    ],
                ],
                'quantity' => 1,
                'amount' => 1295,
                'currency' => 'usd',
                'period' => [
                    'start' => time(),
                    'end' => $periodEnd,
                ],
            ]],
        ]);

        $invoices = Mockery::mock();
        $invoices->shouldReceive('retrieve')
            ->with('in_webhook_integration', [])
            ->andReturn($invoice);

        $invoices->shouldReceive('allLines')
            ->with('in_webhook_integration', ['limit' => 100])
            ->andReturn($lines);

        $subscriptions = Mockery::mock();
        $subscriptions->shouldReceive('retrieve')
            ->with('sub_webhook_integration', [
                'expand' => ['items.data.price'],
            ])
            ->andReturn($subscription);

        $stripe = Mockery::mock(StripeClient::class);
        $stripe->shouldReceive('getService')
            ->with('invoices')
            ->andReturn($invoices);
        $stripe->shouldReceive('getService')
            ->with('subscriptions')
            ->andReturn($subscriptions);

        $connect = Mockery::mock(StripeConnectService::class);
        $connect->shouldReceive('client')->andReturn($stripe);

        $this->app->instance(StripeConnectService::class, $connect);

        config()->set('services.stripe.monthly_price_id', $priceId);
        config()->set('services.stripe.subscription_webhook_secret', self::SECRET);
    }

    private function deliver(string $eventId): \Illuminate\Testing\TestResponse
    {
        $payload = json_encode([
            'id' => $eventId,
            'object' => 'event',
            'type' => 'invoice.paid',
            'livemode' => false,
            'data' => [
                'object' => [
                    'id' => 'in_webhook_integration',
                    'object' => 'invoice',
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $timestamp = time();
        $signature = hash_hmac(
            'sha256',
            $timestamp.'.'.$payload,
            self::SECRET
        );

        return $this->call(
            'POST',
            '/webhooks/stripe-subscriptions',
            [],
            [],
            [],
            [
                'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
                'CONTENT_TYPE' => 'application/json',
            ],
            $payload
        );
    }

    public function test_signed_paid_invoice_activates_monthly_automatically(): void
    {
        $company = $this->company();
        $end = now()->addMonth()->timestamp;

        $this->fakeStripe($company, $end);

        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'inactive',
            'pending_checkout_attempt_id' =>
                '00aabbcc-1111-4222-8333-445566778899',
            'pending_checkout_session_id' => 'cs_test_pending',
            'pending_checkout_expires_at' => now()->addHour(),
        ]);

        $this->assertFalse(
            app(SubscriptionEntitlementService::class)
                ->hasPaidCoverage($company)
        );

        $this->deliver('evt_paid_integration_1')->assertOk();

        $record = CompanySubscription::where('company_id', $company->id)
            ->firstOrFail();

        $this->assertSame('active', $record->status);
        $this->assertFalse($record->stripe_livemode);
        $this->assertSame(
            'sub_webhook_integration',
            $record->stripe_subscription_id
        );
        $this->assertSame($end, $record->paid_through_at->timestamp);
        $this->assertNull($record->pending_checkout_attempt_id);
        $this->assertNull($record->pending_checkout_session_id);
        $this->assertNull($record->pending_checkout_expires_at);
        $this->assertTrue(
            app(SubscriptionEntitlementService::class)
                ->hasPaidCoverage($company)
        );
        $this->assertNotNull(
            SubscriptionEvent::where(
                'stripe_event_id',
                'evt_paid_integration_1'
            )->firstOrFail()->processed_at
        );
    }

    public function test_duplicate_signed_invoice_does_not_duplicate_coverage(): void
    {
        $company = $this->company();
        $end = now()->addMonth()->timestamp;

        $this->fakeStripe($company, $end);

        $this->deliver('evt_paid_integration_2')->assertOk();
        $this->deliver('evt_paid_integration_2')->assertOk();

        $this->assertSame(1, SubscriptionEvent::count());
        $this->assertSame(1, CompanySubscription::count());
        $this->assertSame(
            $end,
            CompanySubscription::firstOrFail()->paid_through_at->timestamp
        );
    }

    public function test_live_invoice_cannot_activate_in_test_environment(): void
    {
        $company = $this->company();
        $this->fakeStripe(
            $company,
            now()->addMonth()->timestamp,
            invoiceLive: true
        );

        $this->deliver('evt_wrong_invoice_mode')->assertStatus(500);

        $this->assertSame(0, CompanySubscription::count());
        $this->assertSame(0, SubscriptionEvent::count());
    }

    public function test_live_subscription_cannot_activate_in_test_environment(): void
    {
        $company = $this->company();
        $this->fakeStripe(
            $company,
            now()->addMonth()->timestamp,
            subscriptionLive: true
        );

        $this->deliver('evt_wrong_subscription_mode')->assertStatus(500);

        $this->assertSame(0, CompanySubscription::count());
        $this->assertSame(0, SubscriptionEvent::count());
    }

    public function test_wrong_invoice_price_cannot_activate_subscription(): void
    {
        $company = $this->company();
        $this->fakeStripe(
            $company,
            now()->addMonth()->timestamp,
            validPrice: false
        );

        $this->deliver('evt_paid_integration_bad')->assertStatus(500);

        $this->assertSame(0, CompanySubscription::count());
        $this->assertSame(0, SubscriptionEvent::count());
    }
}
