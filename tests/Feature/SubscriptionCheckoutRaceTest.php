<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\User;
use App\Services\StripeConnectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Stripe\Checkout\Session;
use Stripe\Price;
use Stripe\StripeClient;
use Tests\TestCase;

class SubscriptionCheckoutRaceTest extends TestCase
{
    use RefreshDatabase;

    private function contractor(): User
    {
        $company = Company::create([
            'name' => 'Subscription Race Contractor',
            'slug' => Company::uniqueSlug('Subscription Race Contractor'),
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

    private function fakeStripe(Company $company, bool $activateEarly): void
    {
        $prices = Mockery::mock();
        $prices->shouldReceive('retrieve')
            ->once()
            ->with('price_race_test', [])
            ->andReturn(Price::constructFrom([
                'id' => 'price_race_test',
                'object' => 'price',
                'active' => true,
                'livemode' => false,
                'unit_amount' => 1295,
                'currency' => 'usd',
                'recurring' => [
                    'interval' => 'month',
                    'interval_count' => 1,
                ],
            ]));

        $sessions = Mockery::mock();
        $sessions->shouldReceive('create')
            ->once()
            ->andReturnUsing(function ($params, $options) use ($company, $activateEarly) {
                $this->assertSame('subscription', $params['mode']);
                $this->assertSame((string) $company->id, $params['client_reference_id']);
                $this->assertSame('price_race_test', $params['line_items'][0]['price']);
                $this->assertStringStartsWith(
                    'cs-sub-'.$company->id.'-',
                    $options['idempotency_key']
                );

                if ($activateEarly) {
                    // Simulate the verified paid-invoice handler
                    // completing before checkout->sessions->create returns.
                    $record = CompanySubscription::where(
                        'company_id',
                        $company->id
                    )->firstOrFail();

                    $record->update([
                        'stripe_customer_id' => 'cus_race_test',
                        'stripe_subscription_id' => 'sub_race_test',
                        'status' => 'active',
                        'paid_through_at' => now()->addMonth(),
                        'pending_checkout_attempt_id' => null,
                        'pending_checkout_session_id' => null,
                        'pending_checkout_expires_at' => null,
                    ]);
                }

                return Session::constructFrom([
                    'id' => 'cs_test_race',
                    'object' => 'checkout.session',
                    'status' => 'open',
                    'url' => 'https://checkout.stripe.com/test/race',
                    'expires_at' => now()->addHour()->timestamp,
                ]);
            });

        $stripe = Mockery::mock(StripeClient::class);
        $stripe->shouldReceive('getService')
            ->with('prices')
            ->andReturn($prices);
        $stripe->shouldReceive('getService')
            ->with('checkout')
            ->andReturn((object) ['sessions' => $sessions]);

        $connect = Mockery::mock(StripeConnectService::class);
        $connect->shouldReceive('client')
            ->andReturn($stripe);

        $this->app->instance(StripeConnectService::class, $connect);

        config()->set('services.stripe.secret', 'sk_test_race_dummy');
        config()->set('services.stripe.monthly_price_id', 'price_race_test');
        config()->set('services.stripe.subscription_webhook_secret', 'whsec_race_dummy');
        config()->set('services.stripe.monthly_launch_enabled', false);
    }

    public function test_early_activation_does_not_resurrect_pending_checkout(): void
    {
        $user = $this->contractor();
        $this->fakeStripe($user->company, true);

        $this->actingAs($user)
            ->post(route('payments.subscription.checkout'))
            ->assertRedirect('https://checkout.stripe.com/test/race');

        $record = CompanySubscription::firstOrFail();

        $this->assertSame('active', $record->status);
        $this->assertSame('sub_race_test', $record->stripe_subscription_id);
        $this->assertNull($record->pending_checkout_attempt_id);
        $this->assertNull($record->pending_checkout_session_id);
        $this->assertNull($record->pending_checkout_expires_at);
    }

    public function test_checkout_persists_pending_session_when_webhook_is_later(): void
    {
        $user = $this->contractor();
        $this->fakeStripe($user->company, false);

        $this->actingAs($user)
            ->post(route('payments.subscription.checkout'))
            ->assertRedirect('https://checkout.stripe.com/test/race');

        $record = CompanySubscription::firstOrFail();

        $this->assertSame('inactive', $record->status);
        $this->assertNull($record->stripe_subscription_id);
        $this->assertNotNull($record->pending_checkout_attempt_id);
        $this->assertSame('cs_test_race', $record->pending_checkout_session_id);
        $this->assertNotNull($record->pending_checkout_expires_at);
    }
}
