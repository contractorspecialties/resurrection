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

        $priceId = config('services.stripe.monthly_price_id');

        abort_unless(
            filled($priceId),
            503,
            'Monthly billing is not configured.'
        );

        $stripe = $stripeConnect->client();
        $price = $stripe->prices->retrieve($priceId, []);

        $secret = (string) config('services.stripe.secret');
        $testMode = str_starts_with($secret, 'sk_test_');
        $liveMode = str_starts_with($secret, 'sk_live_');

        abort_unless(
            ($testMode || $liveMode)
            && (bool) $price->livemode === $liveMode
            && $price->active
            && (int) $price->unit_amount === 1295
            && $price->currency === 'usd'
            && ($price->recurring->interval ?? null) === 'month'
            && (int) ($price->recurring->interval_count ?? 0) === 1,
            503,
            'Monthly pricing could not be verified.'
        );

        return DB::transaction(function () use (
            $company,
            $stripe,
            $priceId,
            $request
        ): RedirectResponse {
            $company = \App\Models\Company::query()
                ->lockForUpdate()
                ->findOrFail($company->id);

            $record = CompanySubscription::query()
                ->where('company_id', $company->id)
                ->lockForUpdate()
                ->first();

            if ($record?->stripe_subscription_id) {
                return redirect()
                    ->route('payments.index')
                    ->with(
                        'status',
                        'A subscription billing record already exists. Use billing management or contact support.'
                    );
            }

            if ($record?->pending_checkout_session_id) {
                $oldSession = $stripe->checkout->sessions->retrieve(
                    $record->pending_checkout_session_id,
                    []
                );

                if (
                    $oldSession->status === 'open'
                    && is_string($oldSession->url ?? null)
                    && str_starts_with($oldSession->url, 'https://')
                ) {
                    return redirect()->away($oldSession->url);
                }

                if ($oldSession->status !== 'expired') {
                    abort(
                        409,
                        'Your subscription checkout is being processed. Please return shortly.'
                    );
                }

                $record->update([
                    'pending_checkout_session_id' => null,
                    'pending_checkout_expires_at' => null,
                ]);
            }

            $attemptId = (string) Str::uuid();

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

            if ($record?->stripe_customer_id) {
                $params['customer'] = $record->stripe_customer_id;
            } else {
                $params['customer_email'] = $request->user()->email;
            }

            $session = $stripe->checkout->sessions->create(
                $params,
                ['idempotency_key' => 'cs-sub-'.$company->id.'-'.$attemptId]
            );

            abort_unless(
                $session->status === 'open'
                && is_string($session->id ?? null)
                && is_string($session->url ?? null)
                && str_starts_with($session->url, 'https://'),
                502,
                'Stripe did not return an open subscription checkout.'
            );

            CompanySubscription::updateOrCreate(
                ['company_id' => $company->id],
                [
                    'pending_checkout_session_id' => $session->id,
                    'pending_checkout_expires_at' => isset($session->expires_at)
                        ? \Illuminate\Support\Carbon::createFromTimestamp(
                            (int) $session->expires_at
                        )
                        : null,
                ]
            );

            return redirect()->away($session->url);
        }, 3);
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
