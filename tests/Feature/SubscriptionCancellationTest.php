<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Services\SubscriptionEntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionCancellationTest extends TestCase
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

    private function entitlement(Company $company): bool
    {
        return app(SubscriptionEntitlementService::class)
            ->hasPaidCoverage($company);
    }

    public function test_canceled_subscription_keeps_prepaid_coverage(): void
    {
        $company = $this->company('Paid Then Canceled');

        CompanySubscription::create([
            'company_id' => $company->id,
            'stripe_customer_id' => 'cus_prepaid_1',
            'stripe_livemode' => false,
            'stripe_subscription_id' => 'sub_prepaid_1',
            'status' => 'canceled',
            'paid_through_at' => now()->addDays(12),
        ]);

        $this->assertTrue($this->entitlement($company));
    }

    public function test_canceled_subscription_expires_automatically(): void
    {
        $company = $this->company('Expired Cancellation');

        CompanySubscription::create([
            'company_id' => $company->id,
            'stripe_customer_id' => 'cus_prepaid_2',
            'stripe_livemode' => false,
            'stripe_subscription_id' => 'sub_prepaid_2',
            'status' => 'canceled',
            'paid_through_at' => now()->subMinute(),
        ]);

        $this->assertFalse($this->entitlement($company));
    }

    public function test_past_due_keeps_only_previously_paid_coverage(): void
    {
        $company = $this->company('Late Renewal');

        $subscription = CompanySubscription::create([
            'company_id' => $company->id,
            'stripe_customer_id' => 'cus_prepaid_3',
            'stripe_livemode' => false,
            'stripe_subscription_id' => 'sub_prepaid_3',
            'status' => 'past_due',
            'paid_through_at' => now()->addDay(),
        ]);

        $this->assertTrue($this->entitlement($company));

        $subscription->update([
            'paid_through_at' => now()->subMinute(),
        ]);

        $this->assertFalse($this->entitlement($company));
    }

    public function test_revocation_overrides_future_paid_through_date(): void
    {
        $company = $this->company('Revoked Subscription');

        CompanySubscription::create([
            'company_id' => $company->id,
            'stripe_customer_id' => 'cus_prepaid_4',
            'stripe_livemode' => false,
            'stripe_subscription_id' => 'sub_prepaid_4',
            'status' => 'canceled',
            'paid_through_at' => now()->addMonth(),
            'revoked_at' => now(),
        ]);

        $this->assertFalse($this->entitlement($company));
    }
}
