<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\SubscriptionEvent;
use App\Services\StripeConnectService;
use App\Services\StripeSubscriptionEventService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Stripe\Event;
use Stripe\StripeClient;
use Stripe\Subscription;
use Tests\TestCase;

class SubscriptionUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function company(string $name): Company
    {
        return Company::create([
            'name' => $name,
            'slug' => Company::uniqueSlug($name),
            'trade' => 'General contracting',
            'city' => 'Test City',
            'state' => 'IN',
            'preferred_customer_contact' => 'email',
        ]);
    }

    private function record(Company $company): CompanySubscription
    {
        return CompanySubscription::create([
            'company_id' => $company->id,
            'stripe_customer_id' => 'cus_update_test',
            'stripe_subscription_id' => 'sub_update_test',
            'status' => 'active',
            'price_cents' => 1295,
            'currency' => 'usd',
            'paid_through_at' => now()->addDays(20),
            'cancel_at_period_end' => false,
        ]);
    }

    private function event(string $id): Event
    {
        return Event::constructFrom([
            'id' => $id,
            'object' => 'event',
            'type' => 'customer.subscription.updated',
            'data' => [
                'object' => [
                    'id' => 'sub_update_test',
                    'object' => 'subscription',
                ],
            ],
        ]);
    }

    private function processor(
        int $companyId,
        bool $cancel,
        string $customer = 'cus_update_test'
    ): StripeSubscriptionEventService {
        $subscription = Subscription::constructFrom([
            'id' => 'sub_update_test',
            'object' => 'subscription',
            'customer' => $customer,
            'metadata' => [
                'company_id' => (string) $companyId,
            ],
            'cancel_at_period_end' => $cancel,
        ]);

        $service = Mockery::mock(
            \Stripe\Service\SubscriptionService::class
        );

        $service->shouldReceive('retrieve')
            ->with('sub_update_test', [])
            ->andReturn($subscription);

        $stripe = Mockery::mock(StripeClient::class);

        $stripe->shouldReceive('getService')
            ->with('subscriptions')
            ->andReturn($service);

        $connect = Mockery::mock(StripeConnectService::class);

        $connect->shouldReceive('client')
            ->andReturn($stripe);

        return new StripeSubscriptionEventService($connect);
    }

    public function test_scheduled_cancellation_preserves_paid_coverage(): void
    {
        $company = $this->company('Cancellation Test');
        $record = $this->record($company);
        $before = $record->paid_through_at->timestamp;

        $processor = $this->processor($company->id, true);
        $processor->process($this->event('evt_update_1'));

        $record->refresh();

        $this->assertTrue($record->cancel_at_period_end);
        $this->assertSame('active', $record->status);
        $this->assertSame($before, $record->paid_through_at->timestamp);
        $this->assertTrue(
            app(\App\Services\SubscriptionEntitlementService::class)
                ->hasPaidCoverage($company)
        );
    }

    public function test_duplicate_update_is_idempotent(): void
    {
        $company = $this->company('Duplicate Update');
        $this->record($company);

        $processor = $this->processor($company->id, true);
        $event = $this->event('evt_update_2');

        $processor->process($event);
        $processor->process($event);

        $this->assertSame(1, SubscriptionEvent::count());
        $this->assertTrue(
            CompanySubscription::firstOrFail()->cancel_at_period_end
        );
    }

    public function test_wrong_customer_cannot_change_cancellation(): void
    {
        $company = $this->company('Customer Protection');
        $this->record($company);

        try {
            $this->processor($company->id, true, 'cus_wrong')
                ->process($this->event('evt_update_3'));

            $this->fail('Expected customer mismatch.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString(
                'customer mismatch',
                $e->getMessage()
            );
        }

        $this->assertFalse(
            CompanySubscription::firstOrFail()->cancel_at_period_end
        );
        $this->assertSame(0, SubscriptionEvent::count());
    }

    public function test_wrong_company_cannot_change_cancellation(): void
    {
        $company = $this->company('Original Company');
        $other = $this->company('Wrong Company');
        $this->record($company);

        try {
            $this->processor($other->id, true)
                ->process($this->event('evt_update_4'));

            $this->fail('Expected company mismatch.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString(
                'company mismatch',
                $e->getMessage()
            );
        }

        $this->assertFalse(
            CompanySubscription::firstOrFail()->cancel_at_period_end
        );
        $this->assertSame(0, SubscriptionEvent::count());
    }

    public function test_unknown_subscription_cannot_create_coverage(): void
    {
        $company = $this->company('Unpaid Company');

        $this->processor($company->id, true)
            ->process($this->event('evt_update_5'));

        $this->assertSame(0, CompanySubscription::count());
        $this->assertSame(1, SubscriptionEvent::count());
    }

    public function test_revoked_subscription_cannot_be_reactivated(): void
    {
        $company = $this->company('Revoked Company');
        $record = $this->record($company);

        $record->update([
            'status' => 'canceled',
            'cancel_at_period_end' => true,
            'revoked_at' => now(),
        ]);

        $this->processor($company->id, false)
            ->process($this->event('evt_update_6'));

        $record->refresh();

        $this->assertSame('canceled', $record->status);
        $this->assertTrue($record->cancel_at_period_end);
        $this->assertNotNull($record->revoked_at);
    }

    public function test_newer_stripe_state_wins_over_stale_event(): void
    {
        $company = $this->company('Out Of Order Company');
        $this->record($company);

        // The webhook snapshot is old, but Stripe currently reports
        // cancellation scheduled. The handler must use current state.
        $this->processor($company->id, true)
            ->process($this->event('evt_update_7'));

        $this->assertTrue(
            CompanySubscription::firstOrFail()->cancel_at_period_end
        );
    }

    public function test_cancellation_can_be_reversed(): void
    {
        $company = $this->company('Cancellation Reversal');
        $record = $this->record($company);

        $record->update(['cancel_at_period_end' => true]);
        $before = $record->paid_through_at->timestamp;

        // Stripe now reports automatic renewal enabled again.
        $this->processor($company->id, false)
            ->process($this->event('evt_update_reversal'));

        $record->refresh();

        $this->assertFalse($record->cancel_at_period_end);
        $this->assertSame('active', $record->status);
        $this->assertSame($before, $record->paid_through_at->timestamp);
    }
}
