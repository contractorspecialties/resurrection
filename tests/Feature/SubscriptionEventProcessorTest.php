<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\SubscriptionEvent;
use App\Services\StripeSubscriptionEventService;
use App\Services\SubscriptionEntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Stripe\Event;
use Tests\TestCase;

class SubscriptionEventProcessorTest extends TestCase
{
    use RefreshDatabase;

    private function company(string $name): Company
    {
        return Company::create([
            'name' => $name,
            'slug' => Company::uniqueSlug($name),
            'trade' => 'General Contractor',
            'city' => 'Charlotte',
            'state' => 'NC',
            'preferred_customer_contact' => 'email',
        ]);
    }

    private function deletedEvent(
        Company $company,
        string $eventId,
        string $customerId = 'cus_owner_test',
        ?int $metadataCompanyId = null
    ): Event {
        return Event::constructFrom([
            'id' => $eventId,
            'object' => 'event',
            'type' => 'customer.subscription.deleted',
            'data' => [
                'object' => [
                    'id' => 'sub_owner_test',
                    'object' => 'subscription',
                    'customer' => $customerId,
                    'metadata' => [
                        'company_id' => (string) (
                            $metadataCompanyId ?? $company->id
                        ),
                    ],
                ],
            ],
        ]);
    }

    private function paidSubscription(Company $company): CompanySubscription
    {
        return CompanySubscription::create([
            'company_id' => $company->id,
            'stripe_customer_id' => 'cus_owner_test',
            'stripe_subscription_id' => 'sub_owner_test',
            'status' => 'active',
            'paid_through_at' => now()->addDays(10),
        ]);
    }

    public function test_deletion_keeps_verified_paid_coverage(): void
    {
        $company = $this->company('Prepaid Contractor');
        $this->paidSubscription($company);

        app(StripeSubscriptionEventService::class)
            ->process($this->deletedEvent($company, 'evt_owner_1'));

        $subscription = CompanySubscription::firstOrFail();

        $this->assertSame('canceled', $subscription->status);
        $this->assertTrue(
            app(SubscriptionEntitlementService::class)
                ->hasPaidCoverage($company)
        );

        $this->assertNotNull(
            SubscriptionEvent::where('stripe_event_id', 'evt_owner_1')
                ->firstOrFail()
                ->processed_at
        );
    }

    public function test_duplicate_deletion_is_idempotent(): void
    {
        $company = $this->company('Duplicate Contractor');
        $this->paidSubscription($company);

        $event = $this->deletedEvent($company, 'evt_owner_2');
        $processor = app(StripeSubscriptionEventService::class);

        $processor->process($event);
        $processor->process($event);

        $this->assertSame(1, SubscriptionEvent::count());
        $this->assertSame('canceled', CompanySubscription::firstOrFail()->status);
    }

    public function test_wrong_customer_cannot_end_subscription(): void
    {
        $company = $this->company('Protected Contractor');
        $this->paidSubscription($company);

        try {
            app(StripeSubscriptionEventService::class)->process(
                $this->deletedEvent($company, 'evt_owner_3', 'cus_wrong')
            );
            $this->fail('Expected customer ownership check.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('customer mismatch', $e->getMessage());
        }

        $this->assertSame('active', CompanySubscription::firstOrFail()->status);
        $this->assertSame(0, SubscriptionEvent::count());
    }

    public function test_wrong_company_cannot_end_subscription(): void
    {
        $company = $this->company('Original Owner');
        $other = $this->company('Different Company');
        $this->paidSubscription($company);

        try {
            app(StripeSubscriptionEventService::class)->process(
                $this->deletedEvent(
                    $company,
                    'evt_owner_4',
                    'cus_owner_test',
                    $other->id
                )
            );
            $this->fail('Expected company ownership check.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('company mismatch', $e->getMessage());
        }

        $this->assertSame('active', CompanySubscription::firstOrFail()->status);
        $this->assertSame(0, SubscriptionEvent::count());
    }

    public function test_deletion_before_invoice_does_not_grant_coverage(): void
    {
        $company = $this->company('Out Of Order Contractor');

        app(StripeSubscriptionEventService::class)
            ->process($this->deletedEvent($company, 'evt_owner_5'));

        $this->assertSame('canceled', CompanySubscription::firstOrFail()->status);
        $this->assertNull(CompanySubscription::firstOrFail()->paid_through_at);
        $this->assertFalse(
            app(SubscriptionEntitlementService::class)
                ->hasPaidCoverage($company)
        );
    }
}
