<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionCheckoutAccessTest extends TestCase
{
    use RefreshDatabase;

    private function contractor(): User
    {
        $company = Company::create([
            'name' => 'Checkout Access Contractor',
            'slug' => Company::uniqueSlug('Checkout Access Contractor'),
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

    public function test_guest_cannot_start_subscription_checkout(): void
    {
        $this->post(route('payments.subscription.checkout'))
            ->assertRedirect(route('login'));
    }

    public function test_regular_contractor_cannot_start_test_subscription(): void
    {
        config()->set('services.stripe.secret', 'sk_test_dummy');
        config()->set('services.stripe.monthly_launch_enabled', false);
        config()->set('services.stripe.subscription_webhook_secret', 'whsec_dummy');

        $this->actingAs($this->contractor())
            ->post(route('payments.subscription.checkout'))
            ->assertStatus(503);
    }

    public function test_live_mode_with_launch_disabled_rejects_checkout(): void
    {
        config()->set('services.stripe.secret', 'sk_live_dummy');
        config()->set('services.stripe.monthly_launch_enabled', false);
        config()->set('services.stripe.subscription_webhook_secret', 'whsec_dummy');

        $this->actingAs($this->contractor())
            ->post(route('payments.subscription.checkout'))
            ->assertStatus(503);
    }

    public function test_guest_cannot_open_billing_portal(): void
    {
        $this->post(route('payments.subscription.portal'))
            ->assertRedirect(route('login'));
    }

    public function test_contractor_without_subscription_cannot_open_portal(): void
    {
        $this->actingAs($this->contractor())
            ->post(route('payments.subscription.portal'))
            ->assertNotFound();
    }
}
