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

            if ($event->type === 'checkout.session.async_payment_failed') {
                $this->handleFailedCheckout($object);
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
                'provider_payment_intent_id' => is_string($session->payment_intent ?? null)
                    ? $session->payment_intent
                    : ($session->payment_intent->id ?? null),
                'paid_at' => now(),
            ]);
        }

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
            'failed_at' => now(),
        ]);
    }
}
