@extends('layouts.app')

@section('title', 'Dashboard — ContractorSpecialties')

@section('content')
<div class="page-head">
    <div>
        <div class="kicker">{{ $company->name }}</div>
        <h1>What needs doing?</h1>
        <p>One screen for customers, estimates, Quick Bills, and money still waiting on somebody.</p>
    </div>

    <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a class="btn btn-secondary" href="{{ route('estimates.create') }}">+ New Estimate</a>
        <a class="btn btn-primary" href="{{ route('quick-bills.create') }}">+ Quick Bill</a>
    </div>
</div>

<div class="grid grid-3">
    <div class="card">
        <div class="kicker">Customers</div>
        <div class="big-number">{{ $company->clients_count }}</div>
        <p class="muted">People you’ve worked for or want to work for.</p>
        <a class="btn btn-secondary" href="{{ route('customers.index') }}">View Customers</a>
    </div>

    <div class="card">
        <div class="kicker">Estimates needing attention</div>
        <div class="big-number">{{ $company->estimates_need_attention_count }}</div>
        <p class="muted">Drafts, revisions, deposits due, or final balances due.</p>
        <a class="btn btn-secondary" href="{{ route('estimates.index') }}">View Estimates</a>
    </div>

    <div class="card">
        <div class="kicker">Quick Bills unpaid</div>
        <div class="big-number">{{ $company->quick_bills_due_count }}</div>
        <p class="muted">Only Quick Bills whose actual status is still payment due.</p>
        <a class="btn btn-secondary" href="{{ route('quick-bills.index') }}">View Quick Bills</a>
    </div>
</div>

<div class="grid grid-2" style="margin-top:20px">
    <div class="card">
        <div class="kicker">Recent estimates</div>
        <h2 style="margin:6px 0 10px">Current truth</h2>

        @forelse($recentEstimates as $estimate)
            <div class="list-row">
                <div>
                    <strong>{{ $estimate->estimate_number }} — {{ $estimate->client->name }}</strong>
                    <div class="muted">
                        {{ $estimate->money($estimate->total_cents) }}
                        •
                        <span class="badge">{{ str_replace('_', ' ', $estimate->status) }}</span>
                    </div>
                </div>

                <a href="{{ route('estimates.show', $estimate) }}">Open →</a>
            </div>
        @empty
            <div class="empty">No estimates yet.</div>
        @endforelse
    </div>

    <div class="card">
        <div class="kicker">Recent Quick Bills</div>
        <h2 style="margin:6px 0 10px">The plumb-bob ledger</h2>

        @forelse($recentQuickBills as $quickBill)
            <div class="list-row">
                <div>
                    <strong>{{ $quickBill->quick_bill_number }} — {{ $quickBill->client->name }}</strong>
                    <div class="muted">
                        {{ $quickBill->money($quickBill->amount_cents) }}
                        •
                        <span class="badge">{{ str_replace('_', ' ', $quickBill->status) }}</span>
                    </div>
                </div>

                <a href="{{ route('quick-bills.show', $quickBill) }}">Open →</a>
            </div>
        @empty
            <div class="empty">No Quick Bills yet.</div>
        @endforelse
    </div>
</div>

<div class="card" style="margin-top:20px">
    <div class="kicker">Recent customers</div>
    <h2 style="margin:6px 0 10px">People paying the bills</h2>

    @forelse($recentClients as $client)
        <div class="list-row">
            <div>
                <strong>{{ $client->name }}</strong>
                <div class="muted">{{ $client->phone ?: $client->email ?: 'No contact info yet' }}</div>
            </div>
            <a href="{{ route('customers.edit', $client) }}">Open →</a>
        </div>
    @empty
        <div class="empty">No customers yet.</div>
    @endforelse
</div>
@endsection
