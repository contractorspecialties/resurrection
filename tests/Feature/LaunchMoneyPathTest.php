<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\Estimate;
use App\Models\EstimateAcceptance;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\QuickBill;
use App\Models\User;
use App\Services\PaymentStateService;
use App\Services\StripeConnectService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use Tests\TestCase;

class LaunchMoneyPathTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposit_advances_job_and_final_payment_marks_estimate_paid(): void
    {
        [$company, $user] = $this->companyAndUser();
        $client = $this->client($company);

        $estimate = $this->estimate(
            company: $company,
            client: $client,
            status: 'deposit_due',
            totalCents: 10000,
            depositCents: 2500,
        );

        $deposit = $estimate->payments()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'provider' => 'stripe',
            'purpose' => 'deposit',
            'status' => 'paid',
            'amount_cents' => 2500,
            'platform_fee_cents' => 200,
            'currency' => 'usd',
            'paid_at' => now(),
        ]);

        app(PaymentStateService::class)->reconcile($deposit);

        $this->assertSame(
            'active_job',
            $estimate->fresh()->status
        );

        $estimate->update(['status' => 'balance_due']);

        $final = $estimate->payments()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'provider' => 'stripe',
            'purpose' => 'final',
            'status' => 'paid',
            'amount_cents' => 7500,
            'platform_fee_cents' => 200,
            'currency' => 'usd',
            'paid_at' => now(),
        ]);

        app(PaymentStateService::class)->reconcile($final);

        $estimate->refresh();

        $this->assertSame('paid', $estimate->status);
        $this->assertSame(10000, $estimate->paidAmountCents());
        $this->assertSame(0, $estimate->balanceDueCents());
    }

    public function test_quick_bill_payment_marks_bill_paid(): void
    {
        [$company, $user] = $this->companyAndUser();
        $client = $this->client($company);
        $quickBill = $this->quickBill($company, $client, 12500);

        $payment = $quickBill->payments()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'provider' => 'stripe',
            'purpose' => 'quick_bill',
            'status' => 'paid',
            'amount_cents' => 12500,
            'platform_fee_cents' => 200,
            'currency' => 'usd',
            'paid_at' => now(),
        ]);

        app(PaymentStateService::class)->reconcile($payment);

        $quickBill->refresh();

        $this->assertSame('paid', $quickBill->status);
        $this->assertNotNull($quickBill->paid_at);
        $this->assertSame(12500, $quickBill->paidAmountCents());
        $this->assertSame(0, $quickBill->balanceDueCents());
    }

    public function test_manual_partial_payment_is_recorded_and_overpayment_is_rejected(): void
    {
        [$company, $user] = $this->companyAndUser();
        $client = $this->client($company);

        $estimate = $this->estimate(
            company: $company,
            client: $client,
            status: 'deposit_due',
            totalCents: 10000,
            depositCents: 5000,
        );

        $this->mock(
            StripeConnectService::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('retireActiveCheckout')
                    ->once()
                    ->andReturnNull();
            }
        );

        $this->actingAs($user)
            ->post(route('payments.manual.estimate', $estimate), [
                'method' => 'cash',
                'amount' => '10.00',
                'note' => 'Partial deposit',
            ])
            ->assertRedirect();

        $estimate->refresh();

        $this->assertSame('deposit_due', $estimate->status);
        $this->assertSame(1000, $estimate->depositPaidCents());

        $payment = $estimate->payments()->firstOrFail();

        $this->assertSame('manual', $payment->provider);
        $this->assertSame('deposit', $payment->purpose);
        $this->assertSame('paid', $payment->status);
        $this->assertSame(1000, $payment->amount_cents);
        $this->assertSame(0, $payment->platform_fee_cents);

        $this->actingAs($user)
            ->post(route('payments.manual.estimate', $estimate), [
                'method' => 'check',
                'amount' => '100.00',
            ])
            ->assertStatus(422);

        $this->assertSame(1, $estimate->payments()->count());
        $this->assertSame(1000, $estimate->fresh()->depositPaidCents());
    }

    public function test_duplicate_stripe_webhook_is_idempotent(): void
    {
        [$company, $user] = $this->companyAndUser();
        $client = $this->client($company);
        $quickBill = $this->quickBill($company, $client, 5000);

        $payment = $quickBill->payments()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'provider' => 'stripe',
            'purpose' => 'quick_bill',
            'status' => 'pending',
            'active_checkout_key' => "quick_bill:{$quickBill->id}",
            'amount_cents' => 5000,
            'platform_fee_cents' => 200,
            'currency' => 'usd',
            'provider_checkout_session_id' => 'cs_test_duplicate',
        ]);

        $secret = 'whsec_launch_test_secret';

        config()->set('services.stripe.webhook_secret', $secret);

        $payload = json_encode([
            'id' => 'evt_launch_duplicate',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'created' => time(),
            'livemode' => false,
            'pending_webhooks' => 1,
            'data' => [
                'object' => [
                    'id' => 'cs_test_duplicate',
                    'object' => 'checkout.session',
                    'payment_status' => 'paid',
                    'payment_intent' => 'pi_launch_duplicate',
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $timestamp = time();

        $signature = hash_hmac(
            'sha256',
            $timestamp.'.'.$payload,
            $secret
        );

        $header = "t={$timestamp},v1={$signature}";

        $this->call(
            'POST',
            route('webhooks.stripe'),
            [],
            [],
            [],
            [
                'HTTP_STRIPE_SIGNATURE' => $header,
                'CONTENT_TYPE' => 'application/json',
            ],
            $payload
        )->assertOk();

        $this->call(
            'POST',
            route('webhooks.stripe'),
            [],
            [],
            [],
            [
                'HTTP_STRIPE_SIGNATURE' => $header,
                'CONTENT_TYPE' => 'application/json',
            ],
            $payload
        )->assertOk();

        $payment->refresh();
        $quickBill->refresh();

        $this->assertSame('paid', $payment->status);
        $this->assertNull($payment->active_checkout_key);
        $this->assertSame(
            'pi_launch_duplicate',
            $payment->provider_payment_intent_id
        );

        $this->assertSame('paid', $quickBill->status);

        $this->assertSame(
            1,
            PaymentEvent::where(
                'provider_event_id',
                'evt_launch_duplicate'
            )->count()
        );
    }

    public function test_active_checkout_key_prevents_duplicate_open_checkout_rows(): void
    {
        [$company, $user] = $this->companyAndUser();
        $client = $this->client($company);
        $quickBill = $this->quickBill($company, $client, 7500);

        $attributes = [
            'company_id' => $company->id,
            'client_id' => $client->id,
            'quick_bill_id' => $quickBill->id,
            'provider' => 'stripe',
            'purpose' => 'quick_bill',
            'status' => 'pending',
            'active_checkout_key' => "quick_bill:{$quickBill->id}",
            'amount_cents' => 7500,
            'platform_fee_cents' => 200,
            'currency' => 'usd',
        ];

        Payment::create($attributes);

        try {
            Payment::create($attributes);

            $this->fail(
                'Duplicate active checkout key was accepted.'
            );
        } catch (QueryException $e) {
            $this->assertSame(
                1,
                Payment::where(
                    'active_checkout_key',
                    "quick_bill:{$quickBill->id}"
                )->count()
            );
        }
    }

    public function test_partial_and_full_refund_reconcile_quick_bill_balance(): void
    {
        [$company, $user] = $this->companyAndUser();
        $client = $this->client($company);
        $quickBill = $this->quickBill($company, $client, 25000);

        $payment = $quickBill->payments()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'provider' => 'stripe',
            'purpose' => 'quick_bill',
            'status' => 'paid',
            'amount_cents' => 25000,
            'platform_fee_cents' => 200,
            'refunded_amount_cents' => 0,
            'currency' => 'usd',
            'provider_payment_intent_id' => 'pi_refund_test',
            'paid_at' => now(),
        ]);

        app(PaymentStateService::class)->reconcile($payment);

        $this->assertSame(
            'paid',
            $quickBill->fresh()->status
        );

        $payment->update([
            'status' => 'paid',
            'refunded_amount_cents' => 15000,
            'refunded_at' => now(),
        ]);

        app(PaymentStateService::class)->reconcile(
            $payment->fresh()
        );

        $quickBill->refresh();

        $this->assertSame('payment_due', $quickBill->status);
        $this->assertSame(10000, $quickBill->paidAmountCents());
        $this->assertSame(15000, $quickBill->balanceDueCents());

        $payment->update([
            'status' => 'refunded',
            'refunded_amount_cents' => 25000,
            'refunded_at' => now(),
        ]);

        app(PaymentStateService::class)->reconcile(
            $payment->fresh()
        );

        $quickBill->refresh();

        $this->assertSame('payment_due', $quickBill->status);
        $this->assertSame(0, $quickBill->paidAmountCents());
        $this->assertSame(25000, $quickBill->balanceDueCents());
    }

    public function test_estimate_with_pending_revision_cannot_be_accepted(): void
    {
        [$company, $user] = $this->companyAndUser();
        $client = $this->client($company);

        $estimate = $this->estimate(
            company: $company,
            client: $client,
            status: 'revision_requested',
            totalCents: 10000,
            depositCents: 2500,
        );

        $this->post(
            route('portal.accept', $estimate->portal_token),
            [
                'signature_name' => 'Test Customer',
                'agree' => '1',
            ]
        )->assertStatus(409);

        $this->assertSame(
            0,
            EstimateAcceptance::where(
                'estimate_id',
                $estimate->id
            )->count()
        );

        $this->assertSame(
            'revision_requested',
            $estimate->fresh()->status
        );
    }

    public function test_company_cannot_record_payment_against_another_companys_estimate(): void
    {
        [$companyA, $userA] = $this->companyAndUser(
            'Company A'
        );

        [$companyB, $userB] = $this->companyAndUser(
            'Company B'
        );

        $clientB = $this->client($companyB);

        $estimateB = $this->estimate(
            company: $companyB,
            client: $clientB,
            status: 'deposit_due',
            totalCents: 10000,
            depositCents: 5000,
        );

        $this->mock(
            StripeConnectService::class,
            function (MockInterface $mock): void {
                $mock->shouldNotReceive(
                    'retireActiveCheckout'
                );
            }
        );

        $this->actingAs($userA)
            ->post(
                route(
                    'payments.manual.estimate',
                    $estimateB
                ),
                [
                    'method' => 'cash',
                    'amount' => '10.00',
                ]
            )
            ->assertNotFound();

        $this->assertSame(
            0,
            $estimateB->payments()->count()
        );
    }

    private function companyAndUser(
        string $name = 'Test Contractor'
    ): array {
        $company = Company::create([
            'name' => $name,
            'slug' => Company::uniqueSlug($name),
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

        return [$company, $user];
    }

    private function client(Company $company): Client
    {
        return $company->clients()->create([
            'name' => 'Test Customer',
            'email' => Str::random(8).'@example.com',
            'phone' => '5555551212',
        ]);
    }

    private function estimate(
        Company $company,
        Client $client,
        string $status,
        int $totalCents,
        int $depositCents,
    ): Estimate {
        return $company->estimates()->create([
            'client_id' => $client->id,
            'estimate_number' => 'EST-'.Str::upper(
                Str::random(8)
            ),
            'portal_token' => Str::random(48),
            'status' => $status,
            'version' => 1,
            'scope_summary' => 'Launch test estimate',
            'subtotal_cents' => $totalCents,
            'tax_cents' => 0,
            'total_cents' => $totalCents,
            'deposit_cents' => $depositCents,
            'tax_rate' => 0,
            'sent_at' => now(),
        ]);
    }

    private function quickBill(
        Company $company,
        Client $client,
        int $amountCents,
    ): QuickBill {
        return $company->quickBills()->create([
            'client_id' => $client->id,
            'quick_bill_number' => 'QB-'.Str::upper(
                Str::random(8)
            ),
            'portal_token' => Str::random(48),
            'status' => 'payment_due',
            'description' => 'Launch test Quick Bill',
            'amount_cents' => $amountCents,
        ]);
    }
}
