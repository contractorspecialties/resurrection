<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Services\SubscriptionEntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionEntitlementTest extends TestCase
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

    private function subscription(Company $company, array $changes = []): void
    {
        CompanySubscription::create(array_merge([
            'company_id' => $company->id,
            'stripe_customer_id' => 'cus_test_'.$company->id,
            'stripe_subscription_id' => 'sub_test_'.$company->id,
            'status' => 'active',
            'price_cents' => 1295,
            'currency' => 'usd',
            'paid_through_at' => now()->addMonth(),
            'cancel_at_period_end' => false,
        ], $changes));
    }

    public function test_free_account_pays_normal_fee(): void
    {
        $company = $this->company('Free Company');

        $this->assertSame(
            200,
            app(SubscriptionEntitlementService::class)
                ->platformFeeCents($company, 200)
        );
    }

    public function test_paid_subscription_waives_fee(): void
    {
        $company = $this->company('Subscribed Company');
        $this->subscription($company);

        $this->assertSame(
            0,
            app(SubscriptionEntitlementService::class)
                ->platformFeeCents($company, 200)
        );
    }

    public function test_expired_subscription_returns_to_per_payment(): void
    {
        $company = $this->company('Expired Company');

        $this->subscription($company, [
            'paid_through_at' => now()->subMinute(),
        ]);

        $this->assertSame(
            200,
            app(SubscriptionEntitlementService::class)
                ->platformFeeCents($company, 200)
        );
    }

    public function test_cancellation_preserves_paid_period(): void
    {
        $company = $this->company('Canceling Company');

        $this->subscription($company, [
            'cancel_at_period_end' => true,
        ]);

        $this->assertSame(
            0,
            app(SubscriptionEntitlementService::class)
                ->platformFeeCents($company, 200)
        );
    }

    public function test_inactive_subscription_does_not_waive_fee(): void
    {
        $company = $this->company('Inactive Company');

        $this->subscription($company, [
            'status' => 'inactive',
        ]);

        $this->assertSame(
            200,
            app(SubscriptionEntitlementService::class)
                ->platformFeeCents($company, 200)
        );
    }

    public function test_revoked_subscription_does_not_waive_fee(): void
    {
        $company = $this->company('Revoked Company');

        $this->subscription($company, [
            'revoked_at' => now(),
        ]);

        $this->assertSame(
            200,
            app(SubscriptionEntitlementService::class)
                ->platformFeeCents($company, 200)
        );
    }

    public function test_one_company_cannot_use_anothers_subscription(): void
    {
        $subscribed = $this->company('Subscriber');
        $free = $this->company('Other Company');

        $this->subscription($subscribed);

        $service = app(SubscriptionEntitlementService::class);

        $this->assertSame(0, $service->platformFeeCents($subscribed, 200));
        $this->assertSame(200, $service->platformFeeCents($free, 200));
    }
}
