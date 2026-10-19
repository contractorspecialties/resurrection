@extends('layouts.app')

@section('title', 'Company Details — ContractorSpecialties')

@section('content')
<div class="page-head">
    <div>
        <div class="kicker">Internal administration</div>
        <h1>{{ $company->name }}</h1>
        <p>{{ $company->trade }} — {{ $company->city }}, {{ $company->state }}</p>
        <a href="{{ route('admin.index') }}">← Back to Companies</a>
    </div>
</div>

<div class="card" style="margin-bottom:20px">
    <h2>Account Details</h2>

    <p><strong>Owner:</strong>
        {{ $company->owner?->name ?? 'Not assigned' }}
    </p>

    <p><strong>Email:</strong>
        {{ $company->owner?->email ?? 'Not available' }}
    </p>

    <p><strong>Joined:</strong>
        {{ $company->created_at?->format('M j, Y') }}
    </p>

    <p><strong>Setup:</strong>
        {{ $company->owner?->onboarding_completed_at
            ? 'Completed'
            : 'Not completed' }}
    </p>

    <p><strong>Stripe:</strong>
        @if($company->hasHealthyStripeConnection())
            Connected and ready
        @elseif($company->stripe_account_id)
            Connected but needs attention
        @else
            Not connected
        @endif
    </p>

    <p>
        <strong>Activity:</strong>
        {{ $company->clients_count }} customers,
        {{ $company->estimates_count }} estimates,
        {{ $company->quick_bills_count }} Quick Bills,
        {{ $company->payments_count }} payment records
    </p>
</div>

<div class="card" style="margin-bottom:20px">
    <h2>Recent Estimates</h2>

    <div style="overflow-x:auto">
        <table style="width:100%;text-align:left">
            <thead>
                <tr>
                    <th>Estimate</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Total</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                @forelse($estimates as $estimate)
                    <tr>
                        <td>{{ $estimate->estimate_number }}</td>
                        <td>{{ $estimate->client?->name ?? 'Unknown' }}</td>
                        <td>{{ str_replace('_', ' ', ucfirst($estimate->status)) }}</td>
                        <td>${{ number_format($estimate->total_cents / 100, 2) }}</td>
                        <td>{{ $estimate->created_at?->format('M j, Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">No estimates yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card" style="margin-bottom:20px">
    <h2>Recent Quick Bills</h2>

    <div style="overflow-x:auto">
        <table style="width:100%;text-align:left">
            <thead>
                <tr>
                    <th>Quick Bill</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($quickBills as $bill)
                    <tr>
                        <td>{{ $bill->quick_bill_number }}</td>
                        <td>{{ $bill->client?->name ?? 'Unknown' }}</td>
                        <td>{{ str_replace('_', ' ', ucfirst($bill->status)) }}</td>
                        <td>${{ number_format($bill->amount_cents / 100, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">No Quick Bills yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card" style="margin-bottom:20px">
    <h2>Recent Payments</h2>

    <div style="overflow-x:auto">
        <table style="width:100%;text-align:left">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Provider</th>
                    <th>Status</th>
                    <th>Amount</th>
                    <th>Refunded</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td>{{ $payment->created_at?->format('M j, Y') }}</td>
                        <td>{{ str_replace('_', ' ', $payment->purpose) }}</td>
                        <td>{{ ucfirst($payment->provider) }}</td>
                        <td>{{ ucfirst($payment->status) }}</td>
                        <td>${{ number_format($payment->amount_cents / 100, 2) }}</td>
                        <td>${{ number_format($payment->refunded_amount_cents / 100, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">No payments yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <h2>Failed Messages</h2>
    <p>Most recent email and SMS delivery failures.</p>

    <div style="overflow-x:auto">
        <table style="width:100%;text-align:left">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Channel</th>
                    <th>Recipient</th>
                    <th>Failure</th>
                </tr>
            </thead>
            <tbody>
                @forelse($failedMessages as $message)
                    <tr>
                        <td>{{ \Illuminate\Support\Carbon::parse($message->created_at)->format('M j, Y') }}</td>
                        <td>{{ strtoupper($message->channel) }}</td>
                        <td>{{ $message->recipient }}</td>
                        <td>{{ $message->error_message ?: 'No error details recorded' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">No failed messages recorded.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
