<?php

namespace App\Http\Controllers;

use App\Services\StripeSubscriptionEventService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeSubscriptionWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        StripeSubscriptionEventService $processor
    ): Response {
        $secret = config('services.stripe.subscription_webhook_secret');

        abort_unless(filled($secret), 503, 'Subscription webhook is not configured.');

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
                $secret
            );
        } catch (UnexpectedValueException|SignatureVerificationException $e) {
            return response('Invalid webhook', 400);
        }

        // Platform billing only; never accept Connect account events here.
        if (filled($event->account ?? null)) {
            return response('Unexpected connected account event', 400);
        }

        $processor->process($event);

        return response('ok', 200);
    }
}
