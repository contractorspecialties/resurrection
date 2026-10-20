<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Services\SubscriptionEntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionEnvironmentBoundaryTest extends TestCase
{
    use RefreshDatabase;

    private function company(): Company
    {
        return Company::create([
            'name' => 'Environment Boundary Contractor',
            'slug' => Company::uniqueSlug('Environment Boundary Contractor'),
            'trade' => 'General contracting',
            'city' => 'Test City',
            'state' => 'IN',
            'preferred_customer_contact' => 'email',
        ]);
    }

    private function subscription(Company $company, ?bool $live): void
    {
        CompanySubscription::create([
            'company_id' => $company->id,
            'stripe_customer_id' => 'cus_boundary_test',
            'stripe_subscription_id' => 'sub_boundary_test',
            'stripe_livemode' => $live,
            'status' => 'active',
            'price_cents' => 1295,
            'currency' => 'usd',
            'paid_through_at' => now()->addMonth(),
        ]);
    }

    private function fee(Company $company, string $key): int
    {
        config()->set('services.stripe.secret', $key);

        return app(SubscriptionEntitlementService::class)
            ->platformFeeCents($company, 200);
    }

    public function test_sandbox_subscription_waives_fee_only_in_test_mode(): void
    {
        $company = $this->company();
        $this->subscription($company, false);

        $this->assertSame(0, $this->fee($company, 'sk_test_boundary'));
        $this->assertSame(200, $this->fee($company, 'sk_live_boundary'));
    }

    public function test_live_subscription_waives_fee_only_in_live_mode(): void
    {
        $company = $this->company();
        $this->subscription($company, true);

        $this->assertSame(0, $this->fee($company, 'sk_live_boundary'));
        $this->assertSame(200, $this->fee($company, 'sk_test_boundary'));
    }

    public function test_unverified_subscription_never_waives_fee(): void
    {
        $company = $this->company();
        $this->subscription($company, null);

        $this->assertSame(200, $this->fee($company, 'sk_live_boundary'));
        $this->assertSame(200, $this->fee($company, 'sk_test_boundary'));
    }

    public function test_missing_or_unrecognized_credentials_fail_closed(): void
    {
        $company = $this->company();
        $this->subscription($company, true);

        $this->assertSame(200, $this->fee($company, ''));
        $this->assertSame(0, $this->fee($company, 'rk_live_boundary'));
        $this->assertSame(200, $this->fee($company, 'unknown'));
    }

    public function test_restricted_test_key_never_waives_live_subscription_fees(): void
    {
        $company = $this->company();
        $this->subscription($company, true);

        $this->assertSame(200, $this->fee($company, 'rk_test_boundary'));
        $this->assertSame(0, $this->fee($company, 'rk_live_boundary'));
    }

    public function test_restricted_live_key_never_waives_test_subscription_fees(): void
    {
        $company = $this->company();
        $this->subscription($company, false);

        $this->assertSame(200, $this->fee($company, 'rk_live_boundary'));
        $this->assertSame(0, $this->fee($company, 'rk_test_boundary'));
    }

    public function test_canceled_paid_coverage_still_respects_environment(): void
    {
        $company = $this->company();
        $this->subscription($company, false);

        CompanySubscription::firstOrFail()->update([
            'status' => 'canceled',
        ]);

        $this->assertSame(0, $this->fee($company, 'sk_test_boundary'));
        $this->assertSame(200, $this->fee($company, 'sk_live_boundary'));
    }
}
