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

    <div class="card">
        <div class="kicker">The two-dollar rule</div>
        <div class="big-number">$2</div>
        <p class="muted">Per successful integrated payment transaction.</p>
        <p>No monthly software subscription for the core tools. Cash and checks can still be recorded manually later without a ContractorSpecialties fee.</p>
    </div>
</div>
@endsection
