<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\User;
use App\Services\StripeConnectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Stripe\Price;
use Stripe\StripeClient;
use Tests\TestCase;

class SubscriptionBillingIdentityTest extends TestCase
{
    use RefreshDatabase;

    private function contractor(): User
    {
        $company = Company::create([
            'name' => 'Identity Test Contractor',
            'slug' => Company::uniqueSlug('Identity Test Contractor'),
            'trade' => 'General contracting',
            'city' => 'Test City',
            'state' => 'IN',
            'preferred_customer_contact' => 'email',
        ]);

        $user = User::factory()->create();
        $user->forceFill([
            'company_id' => $company->id,
            'onboarding_completed_at' => now(),
            'is_internal_admin' => true,
        ])->save();

        return $user;
    }

    private function record(User $user, ?bool $live): CompanySubscription
    {
        return CompanySubscription::create([
            'company_id' => $user->company_id,
            'stripe_customer_id' => 'cus_environment_identity',
            'stripe_livemode' => $live,
            'status' => 'inactive',
        ]);
    }

    private function configure(string $key): void
    {
        config()->set('services.stripe.secret', $key);
        config()->set('services.stripe.monthly_price_id', 'price_identity');
        config()->set('services.stripe.subscription_webhook_secret', 'whsec_identity');
        config()->set('services.stripe.monthly_launch_enabled', true);
    }

    private function fakePrice(bool $live): void
    {
        $prices = Mockery::mock();
        $prices->shouldReceive('retrieve')->once()
            ->with('price_identity', [])
            ->andReturn(Price::constructFrom([
                'id' => 'price_identity',
                'object' => 'price',
                'livemode' => $live,
                'active' => true,
                'unit_amount' => 1295,
                'currency' => 'usd',
                'recurring' => [
                    'interval' => 'month',
                    'interval_count' => 1,
                ],
            ]));

        $stripe = Mockery::mock(StripeClient::class);
        $stripe->shouldReceive('getService')
            ->with('prices')->andReturn($prices);

        $connect = Mockery::mock(StripeConnectService::class);
        $connect->shouldReceive('client')->andReturn($stripe);
        $this->app->instance(StripeConnectService::class, $connect);
    }

    public function test_live_checkout_rejects_sandbox_customer(): void
    {
        $user = $this->contractor();
        $record = $this->record($user, false);
        $this->configure('sk_live_identity');
        $this->fakePrice(true);

        $this->actingAs($user)
            ->post(route('payments.subscription.checkout'))
            ->assertStatus(409);

        $this->assertSame(
            'cus_environment_identity',
            $record->fresh()->stripe_customer_id
        );
        $this->assertNull($record->fresh()->pending_checkout_attempt_id);
    }

    public function test_test_checkout_rejects_live_customer(): void
    {
        $user = $this->contractor();
        $record = $this->record($user, true);
        $this->configure('sk_test_identity');
        $this->fakePrice(false);

        $this->actingAs($user)
            ->post(route('payments.subscription.checkout'))
            ->assertStatus(409);

        $this->assertTrue($record->fresh()->stripe_livemode);
        $this->assertNull($record->fresh()->pending_checkout_attempt_id);
    }

    public function test_unverified_customer_cannot_be_reused(): void
    {
        $user = $this->contractor();
        $record = $this->record($user, null);
        $this->configure('sk_live_identity');
        $this->fakePrice(true);

        $this->actingAs($user)
            ->post(route('payments.subscription.checkout'))
            ->assertStatus(409);

        $this->assertNull($record->fresh()->stripe_livemode);
    }

    public function test_portal_rejects_mismatched_environment(): void
    {
        $user = $this->contractor();
        $this->record($user, false);
        $this->configure('sk_live_identity');

        $this->actingAs($user)
            ->post(route('payments.subscription.portal'))
            ->assertStatus(409);
    }

    public function test_portal_rejects_unverified_environment(): void
    {
        $user = $this->contractor();
        $this->record($user, null);
        $this->configure('sk_test_identity');

        $this->actingAs($user)
            ->post(route('payments.subscription.portal'))
            ->assertStatus(409);
    }

    public function test_portal_accepts_matching_environment(): void
    {
        $user = $this->contractor();
        $this->record($user, false);
        $this->configure('sk_test_identity');

        $sessions = Mockery::mock();
        $sessions->shouldReceive('create')->once()
            ->with([
                'customer' => 'cus_environment_identity',
                'return_url' => route('payments.index'),
            ])
            ->andReturn((object) [
                'url' => 'https://billing.stripe.com/test/identity',
            ]);

        $stripe = Mockery::mock(StripeClient::class);
        $stripe->shouldReceive('getService')
            ->with('billingPortal')
            ->andReturn((object) ['sessions' => $sessions]);

        $connect = Mockery::mock(StripeConnectService::class);
        $connect->shouldReceive('client')->andReturn($stripe);
        $this->app->instance(StripeConnectService::class, $connect);

        $this->actingAs($user)
            ->post(route('payments.subscription.portal'))
            ->assertRedirect('https://billing.stripe.com/test/identity');
    }
}
