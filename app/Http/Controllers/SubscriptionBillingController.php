<?php

namespace App\Http\Controllers;

use App\Models\CompanySubscription;
use App\Services\StripeConnectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class SubscriptionBillingController extends Controller
{
    public function checkout(
        Request $request,
        StripeConnectService $stripeConnect
    ): RedirectResponse {
        $company = $request->user()->company;
        abort_unless($company, 403);

        abort_unless(
            (
                (
                    str_starts_with((string) config('services.stripe.secret'), 'sk_live_')
                    && config('services.stripe.monthly_launch_enabled') === true
                )
                || (
                    $request->user()->is_internal_admin
                    && str_starts_with((string) config('services.stripe.secret'), 'sk_test_')
                )
            )
            && filled(config('services.stripe.subscription_webhook_secret')),
            503,
            'Subscription test billing is not ready.'
        );

        $priceId = config('services.stripe.monthly_price_id');
        abort_unless(filled($priceId), 503, 'Monthly billing is not configured.');

        $stripe = $stripeConnect->client();
        $price = $stripe->prices->retrieve($priceId, []);

        $key = (string) config('services.stripe.secret');
        $test = str_starts_with($key, 'sk_test_');
        $live = str_starts_with($key, 'sk_live_');

        abort_unless(
            ($test || $live)
            && (bool) $price->livemode === $live
            && $price->active
            && (int) $price->unit_amount === 1295
            && $price->currency === 'usd'
            && ($price->recurring->interval ?? null) === 'month'
            && (int) ($price->recurring->interval_count ?? 0) === 1,
            503,
            'Monthly pricing could not be verified.'
        );

        // Commit the attempt BEFORE contacting Stripe.
        $attempt = DB::transaction(function () use ($company): array {
            $lockedCompany = \App\Models\Company::query()
                ->lockForUpdate()
                ->findOrFail($company->id);

            $record = CompanySubscription::query()
                ->where('company_id', $lockedCompany->id)
                ->lockForUpdate()
                ->first();

            if (! $record) {
                $record = CompanySubscription::create([
                    'company_id' => $lockedCompany->id,
                    'status' => 'inactive',
                ]);
            }

            abort_if(
                filled($record->stripe_subscription_id),
                409,
                'A subscription already exists. Use billing management.'
            );

            if (! $record->pending_checkout_attempt_id) {
                $record->update([
                    'pending_checkout_attempt_id' => (string) Str::uuid(),
                ]);
            }

            return [
                'attempt_id' => $record->pending_checkout_attempt_id,
                'session_id' => $record->pending_checkout_session_id,
                'customer_id' => $record->stripe_customer_id,
            ];
        }, 3);

        if ($attempt['session_id']) {
            $existing = $stripe->checkout->sessions->retrieve(
                $attempt['session_id'],
                []
            );

            if (
                $existing->status === 'open'
                && is_string($existing->url ?? null)
                && str_starts_with($existing->url, 'https://')
            ) {
                return redirect()->away($existing->url);
            }

            // Do not create another subscription while the first
            // session may have completed or be awaiting a webhook.
            abort(
                409,
                'Your previous subscription checkout needs reconciliation. Please contact support.'
            );
        }

        $params = [
            'mode' => 'subscription',
            'client_reference_id' => (string) $company->id,
            'line_items' => [
                ['price' => $priceId, 'quantity' => 1],
            ],
            'subscription_data' => [
                'metadata' => [
                    'company_id' => (string) $company->id,
                    'application' => 'contractorspecialties',
                ],
            ],
            'metadata' => [
                'company_id' => (string) $company->id,
                'application' => 'contractorspecialties',
            ],
            'success_url' => route('payments.index')
                . '?subscription_checkout=returned',
            'cancel_url' => route('payments.index'),
        ];

        if ($attempt['customer_id']) {
            $params['customer'] = $attempt['customer_id'];
        } else {
            $params['customer_email'] = $request->user()->email;
        }

        // Every retry uses the SAME key and the SAME parameters.
        // Stripe may return a temporary idempotency conflict if
        // two requests reach it at precisely the same time.
        $session = $stripe->checkout->sessions->create(
            $params,
            [
                'idempotency_key' =>
                    'cs-sub-'.$company->id.'-'.$attempt['attempt_id'],
            ]
        );

        abort_unless(
            $session->status === 'open'
            && is_string($session->id ?? null)
            && is_string($session->url ?? null)
            && str_starts_with($session->url, 'https://'),
            502,
            'Stripe did not return an open subscription checkout.'
        );

        DB::transaction(function () use ($company, $attempt, $session): void {
            $record = CompanySubscription::query()
                ->where('company_id', $company->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                $record->pending_checkout_attempt_id === $attempt['attempt_id'],
                409,
                'Subscription checkout attempt has changed.'
            );

            abort_if(
                filled($record->stripe_subscription_id),
                409,
                'Subscription was already activated.'
            );

            abort_if(
                filled($record->pending_checkout_session_id)
                && $record->pending_checkout_session_id !== $session->id,
                409,
                'Conflicting subscription checkout session.'
            );

            $record->update([
                'pending_checkout_session_id' => $session->id,
                'pending_checkout_expires_at' =>
                    isset($session->expires_at)
                        ? \Illuminate\Support\Carbon::createFromTimestamp(
                            (int) $session->expires_at
                        )
                        : null,
            ]);
        }, 3);

        return redirect()->away($session->url);
    }

    public function portal(
        Request $request,
        StripeConnectService $stripeConnect
    ): RedirectResponse {
        $company = $request->user()->company;

        abort_unless($company, 403);

        $subscription = CompanySubscription::query()
            ->where('company_id', $company->id)
            ->first();

        abort_unless(
            filled($subscription?->stripe_customer_id),
            404,
            'No subscription billing account was found.'
        );

        $session = $stripeConnect->client()
            ->billingPortal->sessions->create([
                'customer' => $subscription->stripe_customer_id,
                'return_url' => route('payments.index'),
            ]);

        if (! is_string($session->url ?? null)
            || ! str_starts_with($session->url, 'https://')) {
            throw new RuntimeException(
                'Stripe did not return a secure billing portal URL.'
            );
        }

        return redirect()->away($session->url);
    }
}
