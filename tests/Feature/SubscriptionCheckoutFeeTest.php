<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Estimate;
use App\Models\QuickBill;
use App\Services\StripeConnectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use ReflectionMethod;
use Tests\TestCase;

class SubscriptionCheckoutFeeTest extends TestCase
{
    use RefreshDatabase;

    private function company(): Company
    {
        return Company::create([
            'name' => 'Checkout Fee Test Contractor',
            'slug' => Company::uniqueSlug('Checkout Fee Test Contractor'),
            'trade' => 'General contracting',
            'city' => 'Test City',
            'state' => 'IN',
            'preferred_customer_contact' => 'email',
        ]);
    }

    private function estimate(Company $company): Estimate
    {
        $client = $company->clients()->create([
            'name' => 'Test Customer',
            'email' => Str::random(8).'@example.com',
            'phone' => '5555551212',
        ]);

        return $company->estimates()->create([
            'client_id' => $client->id,
            'estimate_number' => 'EST-'.Str::upper(Str::random(8)),
            'portal_token' => Str::random(48),
            'status' => 'deposit_due',
            'version' => 1,
            'scope_summary' => 'Subscription checkout fee test',
            'subtotal_cents' => 10000,
            'tax_cents' => 0,
            'total_cents' => 10000,
            'deposit_cents' => 2500,
            'tax_rate' => 0,
            'sent_at' => now(),
        ]);
    }

    private function subscribe(Company $company, bool $expired = false): void
    {
        CompanySubscription::create([
            'company_id' => $company->id,
            'stripe_customer_id' => 'cus_test_'.$company->id,
            'stripe_subscription_id' => 'sub_test_'.$company->id,
            'status' => 'active',
            'price_cents' => 1295,
            'currency' => 'usd',
            'paid_through_at' => $expired
                ? now()->subMinute()
                : now()->addMonth(),
            'cancel_at_period_end' => false,
        ]);
    }

    private function estimateFee(Estimate $estimate, int $amount): int
    {
        return (new ReflectionMethod(
            StripeConnectService::class,
            'estimateFeeCents'
        ))->invoke(
            app(StripeConnectService::class),
            $estimate,
            $amount
        );
    }

    public function test_pay_as_you_go_estimate_keeps_two_dollar_fee(): void
    {
        $company = $this->company();
        $estimate = $this->estimate($company);

        $this->assertSame(200, $this->estimateFee($estimate, 2500));
    }

    public function test_active_monthly_waives_estimate_fee(): void
    {
        $company = $this->company();
        $estimate = $this->estimate($company);
        $this->subscribe($company);

        $this->assertSame(0, $this->estimateFee($estimate, 2500));
    }

    public function test_expired_monthly_restores_estimate_fee(): void
    {
        $company = $this->company();
        $estimate = $this->estimate($company);
        $this->subscribe($company, expired: true);

        $this->assertSame(200, $this->estimateFee($estimate, 2500));
    }

    public function test_small_payment_never_has_fee_equal_to_amount(): void
    {
        $company = $this->company();
        $estimate = $this->estimate($company);

        $this->assertSame(49, $this->estimateFee($estimate, 50));
    }

    private function quickBill(Company $company): QuickBill
    {
        $client = $company->clients()->create([
            'name' => 'Quick Bill Customer',
            'email' => Str::random(8).'@example.com',
            'phone' => '5555551212',
        ]);

        return $company->quickBills()->create([
            'client_id' => $client->id,
            'quick_bill_number' => 'QB-'.Str::upper(Str::random(8)),
            'portal_token' => Str::random(48),
            'status' => 'payment_due',
            'description' => 'Fee waiver test',
            'amount_cents' => 12500,
        ]);
    }

    private function quickBillFee(QuickBill $bill, int $amount): int
    {
        return (new ReflectionMethod(
            StripeConnectService::class,
            'quickBillFeeCents'
        ))->invoke(
            app(StripeConnectService::class),
            $bill,
            $amount
        );
    }

    public function test_pay_as_you_go_quick_bill_keeps_two_dollar_fee(): void
    {
        $company = $this->company();

        $this->assertSame(
            200,
            $this->quickBillFee($this->quickBill($company), 12500)
        );
    }

    public function test_monthly_quick_bill_waives_platform_fee(): void
    {
        $company = $this->company();
        $this->subscribe($company);

        $this->assertSame(
            0,
            $this->quickBillFee($this->quickBill($company), 12500)
        );
    }

    public function test_expired_monthly_restores_quick_bill_fee(): void
    {
        $company = $this->company();
        $this->subscribe($company, expired: true);

        $this->assertSame(
            200,
            $this->quickBillFee($this->quickBill($company), 12500)
        );
    }
}
