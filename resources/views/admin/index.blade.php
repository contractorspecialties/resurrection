@extends('layouts.app')

@section('title', 'Internal Admin — ContractorSpecialties')

@section('content')

<div class="card" style="margin-bottom:24px; border:2px solid #2563eb;">
    <div class="kicker">Internal testing only</div>
    <h2 style="margin:10px 0;">Stripe Monthly Subscription Test</h2>

    <p>
        Test the $12.95/month ContractorSpecialties subscription
        using Stripe's sandbox. No real card will be charged.
    </p>

    <p class="muted">
        This creates a test subscription for the company attached
        to your administrator login. It does not affect other companies.
    </p>

    <form method="POST"
          action="{{ route('admin.subscription-test.checkout') }}">
        @csrf

        <button class="btn btn-primary" type="submit">
            Start $12.95 Test Subscription →
        </button>
    </form>
</div>

<div class="page-head">
    <div>
        <div class="kicker">Internal administration</div>
        <h1>Company Overview</h1>
        <p>Account activity and payment connection health.</p>
    </div>
</div>

<div class="card" style="margin-bottom:20px">
    <div style="display:flex;flex-wrap:wrap;gap:28px">
        <div>
            <strong>{{ number_format($stats['companies']) }}</strong>
            <div>Companies</div>
        </div>
        <div>
            <strong>{{ number_format($stats['stripe_ready']) }}</strong>
            <div>Stripe Ready</div>
        </div>
        <div>
            <strong>{{ number_format($stats['payments_paid']) }}</strong>
            <div>Paid Payments</div>
        </div>
        <div>
            <strong>{{ number_format($stats['payments_failed']) }}</strong>
            <div>Failed Payments</div>
        </div>
        <div>
            <strong>${{ number_format($stats['recorded_platform_fees_cents'] / 100, 2) }}</strong>
            <div>Recorded Fees on Paid Payments</div>
        </div>
    </div>
</div>

<div class="card">
    <h2>Find a Company</h2>

    <form method="GET" action="{{ route('admin.index') }}"
          style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:22px">
        <input type="search" name="q" value="{{ $search }}"
               placeholder="Business, owner, email, or location"
               aria-label="Search companies"
               style="flex:1;min-width:220px">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <div style="overflow-x:auto">
        <table style="width:100%;text-align:left;border-collapse:collapse">
            <thead>
                <tr>
                    <th>Company</th>
                    <th>Owner</th>
                    <th>Activity</th>
                    <th>Payments</th>
                    <th>Stripe</th>
                </tr>
            </thead>
            <tbody>
                @forelse($companies as $company)
                    <tr>
                        <td style="padding:12px 8px">
                            <a href="{{ route('admin.company', $company) }}">
                                <strong>{{ $company->name }}</strong>
                            </a>
                            <div>{{ $company->city }}, {{ $company->state }}</div>
                            <small>Joined {{ $company->created_at?->format('M j, Y') }}</small>
                        </td>
                        <td style="padding:12px 8px">
                            {{ $company->owner?->name ?? 'Not assigned' }}
                            <div>{{ $company->owner?->email }}</div>
                        </td>
                        <td style="padding:12px 8px">
                            {{ $company->clients_count }} customers
                            <div>{{ $company->estimates_count }} estimates</div>
                            <div>{{ $company->quick_bills_count }} Quick Bills</div>
                        </td>
                        <td style="padding:12px 8px">
                            {{ $company->payments_count }} records
                        </td>
                        <td style="padding:12px 8px">
                            @if($company->hasHealthyStripeConnection())
                                Connected
                            @elseif($company->stripe_account_id)
                                Needs attention
                            @else
                                Not connected
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="padding:20px">
                            No companies found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:20px">
        {{ $companies->links() }}
    </div>
</div>
@endsection
