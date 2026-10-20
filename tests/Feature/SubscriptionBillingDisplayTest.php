<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionBillingDisplayTest extends TestCase
{
    use RefreshDatabase;

    private function contractor(): User
    {
        $company = Company::create([
            'name' => 'Billing Display Contractor',
            'slug' => Company::uniqueSlug('Billing Display Contractor'),
            'trade' => 'General contracting',
            'city' => 'Test City',
            'state' => 'IN',
            'preferred_customer_contact' => 'email',
        ]);

        $user = User::factory()->create();

        $user->forceFill([
            'company_id' => $company->id,
            'onboarding_completed_at' => now(),
        ])->save();

        return $user;
    }

    private function configure(): void
    {
        config()->set('services.stripe.secret', 'sk_live_display_fixture');
        config()->set('services.stripe.monthly_launch_enabled', true);
        config()->set('services.stripe.monthly_price_id', 'price_live_display');
        config()->set('services.stripe.subscription_webhook_secret', 'whsec_display');
    }

    public function test_sandbox_subscription_does_not_show_billing_actions(): void
    {
        $user = $this->contractor();
        $this->configure();

        CompanySubscription::create([
            'company_id' => $user->company_id,
            'stripe_customer_id' => 'cus_sandbox_display',
            'stripe_subscription_id' => 'sub_sandbox_display',
            'stripe_livemode' => false,
            'status' => 'active',
            'paid_through_at' => now()->addMonth(),
        ]);

        $this->actingAs($user)
            ->get(route('payments.index'))
            ->assertOk()
            ->assertSee('requires support review')
            ->assertDontSee('Manage monthly billing')
            ->assertDontSee('Start Monthly')
            ->assertDontSee('Monthly is not currently paid');
    }

    public function test_matching_live_subscription_shows_billing_management(): void
    {
        $user = $this->contractor();
        $this->configure();

        CompanySubscription::create([
            'company_id' => $user->company_id,
            'stripe_customer_id' => 'cus_live_display',
            'stripe_subscription_id' => 'sub_live_display',
            'stripe_livemode' => true,
            'status' => 'active',
            'paid_through_at' => now()->addMonth(),
        ]);

        $this->actingAs($user)
            ->get(route('payments.index'))
            ->assertOk()
            ->assertSee('Manage monthly billing')
            ->assertSee('Monthly coverage active through')
            ->assertDontSee('Start Monthly');
    }

    public function test_new_customer_sees_monthly_signup_when_live_enabled(): void
    {
        $user = $this->contractor();
        $this->configure();

        $this->actingAs($user)
            ->get(route('payments.index'))
            ->assertOk()
            ->assertSee('Start Monthly')
            ->assertDontSee('Manage monthly billing');
    }

    public function test_unverified_customer_identity_blocks_billing_actions(): void
    {
        $user = $this->contractor();
        $this->configure();

        CompanySubscription::create([
            'company_id' => $user->company_id,
            'stripe_customer_id' => 'cus_unknown_display',
            'stripe_livemode' => null,
            'status' => 'inactive',
        ]);

        $this->actingAs($user)
            ->get(route('payments.index'))
            ->assertOk()
            ->assertSee('requires support review')
            ->assertDontSee('Manage monthly billing')
            ->assertDontSee('Start Monthly');
    }
}
