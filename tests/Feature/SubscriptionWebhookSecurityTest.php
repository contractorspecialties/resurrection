<?php

namespace Tests\Feature;

use App\Models\SubscriptionEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionWebhookSecurityTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'whsec_test_only_subscription_security';

    private function payload(bool $connected = false): string
    {
        $event = [
            'id' => 'evt_subscription_security_1',
            'object' => 'event',
            'type' => 'invoice.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'in_security_test',
                    'object' => 'invoice',
                ],
            ],
        ];

        if ($connected) {
            $event['account'] = 'acct_unexpected_connected_account';
        }

        return json_encode($event, JSON_THROW_ON_ERROR);
    }

    private function signature(string $payload): string
    {
        $timestamp = time();

        $hash = hash_hmac(
            'sha256',
            $timestamp.'.'.$payload,
            self::SECRET
        );

        return "t={$timestamp},v1={$hash}";
    }

    public function test_missing_webhook_secret_fails_closed(): void
    {
        config()->set('services.stripe.subscription_webhook_secret', null);

        $this->post('/webhooks/stripe-subscriptions', [], [
            'Stripe-Signature' => 'invalid',
        ])->assertStatus(503);

        $this->assertSame(0, SubscriptionEvent::count());
    }

    public function test_invalid_signature_is_rejected(): void
    {
        config()->set(
            'services.stripe.subscription_webhook_secret',
            self::SECRET
        );

        $this->call(
            'POST',
            '/webhooks/stripe-subscriptions',
            [],
            [],
            [],
            [
                'HTTP_STRIPE_SIGNATURE' => 't=123,v1=invalid',
                'CONTENT_TYPE' => 'application/json',
            ],
            $this->payload()
        )->assertStatus(400);

        $this->assertSame(0, SubscriptionEvent::count());
    }

    public function test_connected_account_event_is_rejected(): void
    {
        config()->set(
            'services.stripe.subscription_webhook_secret',
            self::SECRET
        );

        $payload = $this->payload(true);

        $this->call(
            'POST',
            '/webhooks/stripe-subscriptions',
            [],
            [],
            [],
            [
                'HTTP_STRIPE_SIGNATURE' => $this->signature($payload),
                'CONTENT_TYPE' => 'application/json',
            ],
            $payload
        )->assertStatus(400);

        $this->assertSame(0, SubscriptionEvent::count());
    }

    public function test_valid_duplicate_event_is_processed_once(): void
    {
        config()->set(
            'services.stripe.subscription_webhook_secret',
            self::SECRET
        );

        $payload = $this->payload();

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->call(
                'POST',
                '/webhooks/stripe-subscriptions',
                [],
                [],
                [],
                [
                    'HTTP_STRIPE_SIGNATURE' => $this->signature($payload),
                    'CONTENT_TYPE' => 'application/json',
                ],
                $payload
            )->assertOk();
        }

        $this->assertSame(1, SubscriptionEvent::count());
        $this->assertNotNull(
            SubscriptionEvent::firstOrFail()->processed_at
        );
    }
}
