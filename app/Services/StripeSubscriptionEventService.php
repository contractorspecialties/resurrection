<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\SubscriptionEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Stripe\Event;
use Stripe\StripeClient;

class StripeSubscriptionEventService
{
    public function __construct(
        private StripeConnectService $stripeConnect
    ) {}

    public function process(Event $event): void
    {
        // Resolve and verify Stripe data before opening a DB transaction.
        $change = $this->resolveChange($event);

        DB::transaction(function () use ($event, $change): void {
            $record = SubscriptionEvent::query()
                ->where('stripe_event_id', $event->id)
                ->lockForUpdate()
                ->first();

            if ($record?->processed_at) {
                return;
            }

            if (! $record) {
                $record = SubscriptionEvent::create([
                    'stripe_event_id' => $event->id,
                    'event_type' => $event->type,
                    'stripe_customer_id' => $change['customer_id'] ?? null,
                    'stripe_subscription_id' => $change['subscription_id'] ?? null,
                    'payload' => $event->toArray(),
                ]);
            }

            if ($change['action'] === 'paid') {
                $this->recordPaidCoverage($change);
            } elseif ($change['action'] === 'ended') {
                $this->recordEndedSubscription($change);
            }

            $record->update([
                'processed_at' => now(),
                'processing_error' => null,
            ]);
        }, 3);
    }

    private function resolveChange(Event $event): array
    {
        if ($event->type === 'invoice.paid') {
            return $this->verifiedPaidInvoice($event->data->object);
        }

        if ($event->type === 'customer.subscription.deleted') {
            $subscription = $event->data->object;

            return [
                'action' => 'ended',
                'subscription_id' => $subscription->id,
                'customer_id' => $this->stripeId($subscription->customer ?? null),
                'company_id' => $subscription->metadata->company_id ?? null,
            ];
        }

        // Other subscribed events are acknowledged but cannot grant coverage.
        return ['action' => 'none'];
    }

    private function verifiedPaidInvoice(object $invoice): array
    {
        $client = $this->stripeConnect->client();

        // Retrieve the authoritative current invoice, not just webhook data.
        $invoice = $client->invoices->retrieve($invoice->id, []);

        if (
            ($invoice->status ?? null) !== 'paid'
            || (int) ($invoice->amount_paid ?? 0) < 1295
            || ($invoice->currency ?? null) !== 'usd'
        ) {
            return ['action' => 'none'];
        }

        $subscriptionId = $this->stripeId(
            $invoice->parent->subscription_details->subscription
                ?? $invoice->subscription
                ?? null
        );

        if (! $subscriptionId) {
            // Unrelated invoice, not one of our subscriptions.
            return ['action' => 'none'];
        }

        $subscription = $client->subscriptions->retrieve(
            $subscriptionId,
            ['expand' => ['items.data.price']]
        );

        $invoiceCustomer = $this->stripeId($invoice->customer ?? null);
        $subscriptionCustomer = $this->stripeId($subscription->customer ?? null);

        if (
            ! $invoiceCustomer
            || ! $subscriptionCustomer
            || $invoiceCustomer !== $subscriptionCustomer
        ) {
            throw new RuntimeException('Invoice customer ownership mismatch.');
        }

        $companyId = $subscription->metadata->company_id ?? null;
        $expectedPrice = config('services.stripe.monthly_price_id');

        if (
            ! ctype_digit((string) $companyId)
            || ! filled($expectedPrice)
        ) {
            throw new RuntimeException(
                'Subscription has no valid company or configured price.'
            );
        }

        $matchingItemIds = [];

        foreach ($subscription->items->data as $item) {
            $price = $item->price;

            if (
                $price->id === $expectedPrice
                && (int) $price->unit_amount === 1295
                && $price->currency === 'usd'
                && ($price->recurring->interval ?? null) === 'month'
                && (int) ($price->recurring->interval_count ?? 1) === 1
                && (int) $item->quantity === 1
            ) {
                $matchingItemIds[] = $item->id;
            }
        }

        if (count($matchingItemIds) !== 1
            || count($subscription->items->data) !== 1) {
            throw new RuntimeException('Unexpected subscription price or items.');
        }

        $lineItems = $client->invoices->allLines(
            $invoice->id,
            ['limit' => 100]
        );

        if ($lineItems->has_more) {
            throw new RuntimeException('Invoice has too many line items to verify.');
        }

        $paidThrough = null;

        foreach ($lineItems->data as $line) {
            $lineItemId = $this->stripeId(
                $line->parent->subscription_item_details->subscription_item
                    ?? null
            );

            $linePriceId = $this->stripeId(
                $line->pricing->price_details->price
                    ?? $line->price
                    ?? null
            );

            if (
                $lineItemId !== $matchingItemIds[0]
                || $linePriceId !== $expectedPrice
                || (int) ($line->quantity ?? 0) !== 1
                || (int) ($line->amount ?? 0) <= 0
                || ($line->currency ?? null) !== 'usd'
                || ($line->parent->subscription_item_details->proration ?? false)
            ) {
                continue;
            }

            $end = (int) ($line->period->end ?? 0);

            if ($end > time() && ($paidThrough === null || $end > $paidThrough)) {
                $paidThrough = $end;
            }
        }

        if (! $paidThrough) {
            throw new RuntimeException('Paid invoice lacks a valid coverage period.');
        }

        return [
            'action' => 'paid',
            'company_id' => (int) $companyId,
            'subscription_id' => $subscriptionId,
            'customer_id' => $this->stripeId($subscription->customer),
            'paid_through' => $paidThrough,
            'status' => $subscription->status,
            'cancel_at_period_end' => (bool) $subscription->cancel_at_period_end,
        ];
    }

    private function recordPaidCoverage(array $change): void
    {
        $company = Company::query()
            ->lockForUpdate()
            ->findOrFail($change['company_id']);

        $record = CompanySubscription::query()
            ->where('company_id', $company->id)
            ->lockForUpdate()
            ->first();

        if (
            ! is_string($change['customer_id'] ?? null)
            || $change['customer_id'] === ''
        ) {
            throw new RuntimeException('Paid subscription has no Stripe customer.');
        }

        if (
            $record?->stripe_customer_id
            && $record->stripe_customer_id !== $change['customer_id']
        ) {
            throw new RuntimeException('Stripe customer ownership mismatch.');
        }

        if ($record
            && $record->stripe_subscription_id
            && $record->stripe_subscription_id !== $change['subscription_id']) {
            throw new RuntimeException(
                'Company has a different recorded Stripe subscription.'
            );
        }

        if (
            $record?->revoked_at
            || $record?->status === 'canceled'
        ) {
            // Delayed invoice events cannot reactivate ended subscriptions.
            return;
        }

        $previousEnd = $record?->paid_through_at?->timestamp ?? 0;
        $paidThrough = max($previousEnd, $change['paid_through']);

        CompanySubscription::updateOrCreate(
            ['company_id' => $company->id],
            [
                'stripe_customer_id' => $change['customer_id'],
                'stripe_subscription_id' => $change['subscription_id'],
                'status' => $change['status'],
                'price_cents' => 1295,
                'currency' => 'usd',
                'paid_through_at' => Carbon::createFromTimestamp($paidThrough),
                'cancel_at_period_end' => $change['cancel_at_period_end'],
            ]
        );
    }

    private function recordEndedSubscription(array $change): void
    {
        $subscriptionId = $change['subscription_id'];

        $record = CompanySubscription::query()
            ->where('stripe_subscription_id', $subscriptionId)
            ->lockForUpdate()
            ->first();

        if (! $record) {
            $companyId = $change['company_id'] ?? null;

            if (! ctype_digit((string) $companyId)) {
                throw new RuntimeException(
                    'Deleted subscription has no valid company mapping.'
                );
            }

            Company::query()
                ->lockForUpdate()
                ->findOrFail((int) $companyId);

            $existing = CompanySubscription::query()
                ->where('company_id', (int) $companyId)
                ->lockForUpdate()
                ->first();

            if ($existing && $existing->stripe_subscription_id
                && $existing->stripe_subscription_id !== $subscriptionId) {
                throw new RuntimeException(
                    'Deleted subscription conflicts with company billing record.'
                );
            }

            $record = $existing ?? new CompanySubscription([
                'company_id' => (int) $companyId,
            ]);

            $record->stripe_subscription_id = $subscriptionId;
            $record->stripe_customer_id = $change['customer_id'];
        }

        if (
            ! ctype_digit((string) ($change['company_id'] ?? null))
            || (int) $change['company_id'] !== (int) $record->company_id
        ) {
            throw new RuntimeException('Deleted subscription company mismatch.');
        }

        if (
            ! is_string($change['customer_id'] ?? null)
            || $change['customer_id'] === ''
            || (
                $record->stripe_customer_id
                && $record->stripe_customer_id !== $change['customer_id']
            )
        ) {
            throw new RuntimeException('Deleted subscription customer mismatch.');
        }

        // Preserve verified paid coverage. Ending a subscription must
        // not erase a period for which the contractor already paid.
        $record->status = 'canceled';
        $record->cancel_at_period_end = false;
        $record->save();
    }

    private function stripeId(mixed $value): ?string
    {
        if (is_string($value) && $value !== '') {
            return $value;
        }

        return is_object($value) ? ($value->id ?? null) : null;
    }
}
