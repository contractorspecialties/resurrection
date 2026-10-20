<?php

namespace App\Services;

class SubscriptionInvoiceLineVerifier
{
    public function coverageEnd(
        object $line,
        string $expectedItemId,
        string $expectedPriceId,
        int $currentTimestamp
    ): ?int {
        $details = $line->parent->subscription_item_details ?? null;

        $itemId = $details->subscription_item ?? null;
        $priceId = $line->pricing->price_details->price
            ?? $line->price->id
            ?? $line->price
            ?? null;

        if (
            $itemId !== $expectedItemId
            || $priceId !== $expectedPriceId
            || ($details->proration ?? false)
            || (int) ($line->quantity ?? 0) !== 1
            || (int) ($line->amount ?? 0) !== 1295
            || ($line->currency ?? null) !== 'usd'
        ) {
            return null;
        }

        $start = (int) ($line->period->start ?? 0);
        $end = (int) ($line->period->end ?? 0);

        if ($start <= 0 || $end <= $start || $end <= $currentTimestamp) {
            return null;
        }

        return $end;
    }
}
