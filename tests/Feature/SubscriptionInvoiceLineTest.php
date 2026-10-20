<?php

namespace Tests\Feature;

use App\Services\SubscriptionInvoiceLineVerifier;
use PHPUnit\Framework\TestCase;

class SubscriptionInvoiceLineTest extends TestCase
{
    private function line(array $overrides = []): object
    {
        $line = [
            'parent' => [
                'subscription_item_details' => [
                    'subscription_item' => 'si_expected',
                    'proration' => false,
                ],
            ],
            'pricing' => [
                'price_details' => [
                    'price' => 'price_expected',
                ],
            ],
            'quantity' => 1,
            'amount' => 1295,
            'currency' => 'usd',
            'period' => [
                'start' => 1700000000,
                'end' => 1900000000,
            ],
        ];

        foreach ($overrides as $key => $value) {
            $line[$key] = $value;
        }

        return json_decode(json_encode($line));
    }

    private function verify(object $line): ?int
    {
        return (new SubscriptionInvoiceLineVerifier())->coverageEnd(
            $line,
            'si_expected',
            'price_expected',
            1800000000
        );
    }

    public function test_valid_monthly_invoice_line_grants_coverage(): void
    {
        $this->assertSame(1900000000, $this->verify($this->line()));
    }

    public function test_different_price_is_rejected(): void
    {
        $this->assertNull($this->verify($this->line([
            'pricing' => ['price_details' => ['price' => 'price_wrong']],
        ])));
    }

    public function test_different_subscription_item_is_rejected(): void
    {
        $this->assertNull($this->verify($this->line([
            'parent' => [
                'subscription_item_details' => [
                    'subscription_item' => 'si_wrong',
                    'proration' => false,
                ],
            ],
        ])));
    }

    public function test_proration_is_rejected(): void
    {
        $this->assertNull($this->verify($this->line([
            'parent' => [
                'subscription_item_details' => [
                    'subscription_item' => 'si_expected',
                    'proration' => true,
                ],
            ],
        ])));
    }

    public function test_discounted_line_is_not_treated_as_full_payment(): void
    {
        $this->assertNull($this->verify($this->line([
            'amount' => 500,
        ])));
    }

    public function test_wrong_currency_is_rejected(): void
    {
        $this->assertNull($this->verify($this->line([
            'currency' => 'eur',
        ])));
    }

    public function test_multiple_quantity_is_rejected(): void
    {
        $this->assertNull($this->verify($this->line([
            'quantity' => 2,
        ])));
    }

    public function test_expired_period_is_rejected(): void
    {
        $this->assertNull($this->verify($this->line([
            'period' => [
                'start' => 1700000000,
                'end' => 1750000000,
            ],
        ])));
    }

    public function test_missing_period_is_rejected(): void
    {
        $this->assertNull($this->verify($this->line([
            'period' => null,
        ])));
    }
}
