@extends('layouts.app')

@section('title', 'Payments — ContractorSpecialties')

@section('content')
<div class="page-head">
    <div>
        <div class="kicker">Payments</div>
        <h1>Get paid without becoming a banker.</h1>
        <p>Connect Stripe once. ContractorSpecialties earns $2 when an integrated online payment succeeds.</p>
    </div>
</div>

<div class="grid grid-2">
    <div class="card">
        <div class="kicker">Stripe</div>
        <h2 style="margin:8px 0">
            @if($company->hasHealthyStripeConnection())
                Connected and ready
            @elseif($company->stripe_account_id)
                Setup in progress
            @else
                Not connected
            @endif
        </h2>

        @if($company->hasHealthyStripeConnection())
            <div class="status" style="margin:14px 0">
                Online payments are enabled.
            </div>
            <p class="muted">Stripe account: {{ $company->stripe_account_id }}</p>
        @else
            <p class="muted">
                Stripe handles the payment details and payout setup. ContractorSpecialties does not store customer card numbers.
            </p>

            <form method="POST" action="{{ route('payments.stripe.connect') }}">
                @csrf
                <button class="btn btn-primary" type="submit">
                    {{ $company->stripe_account_id ? 'Continue Stripe Setup →' : 'Connect Stripe →' }}
                </button>
            </form>
        @endif
    </div>

    @php
        $monthlySubscription = \App\Models\CompanySubscription::query()
            ->where('company_id', $company->id)
            ->first();

        $monthlyActive = app(\App\Services\SubscriptionEntitlementService::class)
            ->hasPaidCoverage($company);

        $stripeKey = (string) config('services.stripe.secret');
        $currentLiveMode = (str_starts_with($stripeKey, 'sk_live_') || str_starts_with($stripeKey, 'rk_live_'))
            ? true
            : ((str_starts_with($stripeKey, 'sk_test_') || str_starts_with($stripeKey, 'rk_test_')) ? false : null);

        $hasBillingIdentity = filled($monthlySubscription?->stripe_customer_id)
            || filled($monthlySubscription?->stripe_subscription_id)
            || filled($monthlySubscription?->pending_checkout_attempt_id)
            || filled($monthlySubscription?->pending_checkout_session_id);

        $billingEnvironmentMatches = $currentLiveMode !== null
            && $monthlySubscription?->stripe_livemode !== null
            && $monthlySubscription->stripe_livemode === $currentLiveMode;

        $billingNeedsReconciliation = $hasBillingIdentity
            && ! $billingEnvironmentMatches;

        $liveMonthlyConfigured =
            (str_starts_with((string) config('services.stripe.secret'), 'sk_live_') || str_starts_with((string) config('services.stripe.secret'), 'rk_live_'))
            && config('services.stripe.monthly_launch_enabled') === true
            && filled(config('services.stripe.monthly_price_id'))
            && filled(config('services.stripe.subscription_webhook_secret'));
    @endphp

    <div class="card">
        <div class="kicker">Choose how you pay</div>
        <h2 style="margin:8px 0">Pay as You Go</h2>
        <div class="big-number">$0/month</div>
        <p>$2 per eligible integrated online payment, capped at $5 per estimate or project.</p>
        <p class="muted">All core features included. Stripe's own payment processing fees are separate.</p>

        <hr style="margin:24px 0">

        <h2 style="margin:8px 0">Monthly</h2>
        <div class="big-number">$12.95/month</div>
        <p>All the same core features, without additional ContractorSpecialties transaction fees during paid coverage.</p>
        <p class="muted">Stripe's own payment processing fees still apply.</p>

        @if($monthlyActive)
            <div class="status" style="margin:14px 0">
                Monthly coverage active through
                {{ $monthlySubscription->paid_through_at->format('M j, Y') }}.
            </div>
        @elseif($billingNeedsReconciliation)
            <p class="muted">
                This account has a billing record from a different or unverified
                Stripe environment. Monthly billing requires support review.
                Your core tools remain available.
            </p>
        @elseif($monthlySubscription?->stripe_subscription_id)
            <p class="muted">
                Monthly coverage is not currently active. Your core tools
                remain available on Pay as You Go.
            </p>
        @endif

        @if($billingNeedsReconciliation)
            <p class="muted">
                Monthly billing management and signup are temporarily
                unavailable for this account. Please contact support.
            </p>
        @elseif($monthlySubscription?->stripe_customer_id && $billingEnvironmentMatches)
            <form method="POST" action="{{ route('payments.subscription.portal') }}">
                @csrf
                <button class="btn" type="submit">Manage monthly billing →</button>
            </form>
        @elseif($liveMonthlyConfigured && ! $hasBillingIdentity)
            <form method="POST" action="{{ route('payments.subscription.checkout') }}">
                @csrf
                <button class="btn btn-primary" type="submit">
                    Start Monthly — $12.95/month →
                </button>
            </form>
        @else
            <p class="muted">Monthly signup will be available at launch.</p>
        @endif

        <p class="muted">
            Canceling Monthly returns you to Pay as You Go after your paid period.
            Your account, customers, estimates, and records stay intact.
        </p>
    </div>
</div>
@endsection
