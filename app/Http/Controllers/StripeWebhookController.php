<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Services\PaymentStateService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentStateService $paymentState): Response
    {
        $secret = config('services.stripe.webhook_secret');

        abort_unless(filled($secret), 500, 'Stripe webhook secret is not configured.');

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
                $secret
            );
        } catch (\UnexpectedValueException|\Stripe\Exception\SignatureVerificationException $e) {
            return response('Invalid webhook', 400);
        }

        if (PaymentEvent::where('provider_event_id', $event->id)->exists()) {
            return response('Already processed', 200);
        }

        DB::transaction(function () use ($event, $paymentState) {
            $record = PaymentEvent::create([
                'provider' => 'stripe',
                'provider_event_id' => $event->id,
                'event_type' => $event->type,
                'connected_account_id' => $event->account ?? null,
                'payload' => $event->toArray(),
            ]);

            $object = $event->data->object;

            if (in_array($event->type, [
                'checkout.session.completed',
                'checkout.session.async_payment_succeeded',
            ], true)) {
                $this->handleSuccessfulCheckout($object, $paymentState);
            }

            if (in_array($event->type, [
                'checkout.session.async_payment_failed',
                'checkout.session.expired',
            ], true)) {
                $this->handleFailedCheckout($object);
            }

            if ($event->type === 'charge.refunded') {
                $this->handleRefundedCharge($object, $paymentState);
            }

            $record->update(['processed_at' => now()]);
        });

        return response('ok', 200);
    }

    private function handleSuccessfulCheckout(
        object $session,
        PaymentStateService $paymentState
    ): void {
        if (($session->payment_status ?? null) !== 'paid') {
            return;
        }

        $payment = Payment::query()
            ->where('provider', 'stripe')
            ->where('provider_checkout_session_id', $session->id)
            ->lockForUpdate()
            ->first();

        if (! $payment) {
            return;
        }

        if ($payment->status !== 'paid') {
            $payment->update([
                'status' => 'paid',
                'active_checkout_key' => null,
                'provider_payment_intent_id' => is_string($session->payment_intent ?? null)
                    ? $session->payment_intent
                    : ($session->payment_intent->id ?? null),
                'paid_at' => now(),
            ]);
        }

        $paymentState->reconcile($payment->fresh());
    }

    private function handleRefundedCharge(
        object $charge,
        PaymentStateService $paymentState
    ): void {
        $paymentIntentId = is_string($charge->payment_intent ?? null)
            ? $charge->payment_intent
            : ($charge->payment_intent->id ?? null);

        $payment = Payment::query()
            ->where('provider', 'stripe')
            ->where(function ($query) use ($charge, $paymentIntentId) {
                if ($paymentIntentId) {
                    $query->where(
                        'provider_payment_intent_id',
                        $paymentIntentId
                    );
                }

                if (isset($charge->id)) {
                    if ($paymentIntentId) {
                        $query->orWhere(
                            'provider_charge_id',
                            $charge->id
                        );
                    } else {
                        $query->where(
                            'provider_charge_id',
                            $charge->id
                        );
                    }
                }
            })
            ->lockForUpdate()
            ->first();

        if (! $payment) {
            return;
        }

        $refundedAmount = min(
            $payment->amount_cents,
            max(0, (int) ($charge->amount_refunded ?? 0))
        );

        if ($refundedAmount <= 0) {
            return;
        }

        $payment->update([
            'status' => $refundedAmount >= $payment->amount_cents
                ? 'refunded'
                : 'paid',
            'provider_charge_id' => $charge->id ?? $payment->provider_charge_id,
            'refunded_amount_cents' => $refundedAmount,
            'refunded_at' => now(),
            'active_checkout_key' => null,
        ]);

        $paymentState->reconcile($payment->fresh());
    }

    private function handleFailedCheckout(object $session): void
    {
        $payment = Payment::query()
            ->where('provider', 'stripe')
            ->where('provider_checkout_session_id', $session->id)
            ->lockForUpdate()
            ->first();

        if (! $payment || $payment->status === 'paid') {
            return;
        }

        $payment->update([
            'status' => 'failed',
            'active_checkout_key' => null,
            'failed_at' => now(),
        ]);
    }
}
