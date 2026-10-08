@extends('layouts.app')

@section('title', $estimate->estimate_number . ' — ContractorSpecialties')

@section('content')

@include('estimates._message-actions')

<div class="page-head">
    <div>
        <div class="kicker">{{ $estimate->estimate_number }} • version {{ $estimate->version }}</div>
        <h1>{{ $estimate->client->name }}</h1>
        <p><span class="badge">{{ str_replace('_', ' ', $estimate->status) }}</span></p>
    </div>

    <div style="display:flex;gap:10px;flex-wrap:wrap">
        @if(in_array($estimate->status, ['draft', 'revision_requested'], true))
            <a class="btn btn-secondary" href="{{ route('estimates.edit', $estimate) }}">{{ $estimate->status === 'revision_requested' ? 'Revise Estimate' : 'Edit' }}</a>
        @endif

        @if($estimate->status === 'draft')
            <form method="POST" action="{{ route('estimates.mark-sent', $estimate) }}">
                @csrf
                <button class="btn btn-primary" type="submit">Make Customer Link Live →</button>
            </form>
        @endif

        @if($estimate->status === 'active_job' && $estimate->balanceDueCents() > 0)
            <form method="POST" action="{{ route('estimates.balance-due', $estimate) }}">
                @csrf
                <button class="btn btn-primary" type="submit">Request Final Balance →</button>
            </form>
        @endif
    </div>
</div>

<div class="grid grid-3">
    <div class="card">
        <div class="kicker">Estimate total</div>
        <div class="big-number">{{ $estimate->money($estimate->total_cents) }}</div>
    </div>
    <div class="card">
        <div class="kicker">Paid</div>
        <div class="big-number">{{ $estimate->money($estimate->paidAmountCents()) }}</div>
    </div>
    <div class="card">
        <div class="kicker">Balance</div>
        <div class="big-number">{{ $estimate->money($estimate->balanceDueCents()) }}</div>
    </div>
</div>

@if(in_array($estimate->status, ['active_job', 'balance_due'], true))
<div class="card" style="margin-top:20px">
    <div class="kicker">Job schedule</div>
    <h2 style="margin:8px 0">When are you doing the work?</h2>

    <form method="POST" action="{{ route('estimates.job-date', $estimate) }}">
        @csrf

        <div style="display:flex;gap:12px;align-items:end;flex-wrap:wrap">
            <div class="field" style="margin:0;min-width:220px">
                <label for="job_date">Job date</label>
                <input
                    id="job_date"
                    name="job_date"
                    type="date"
                    value="{{ old('job_date', $estimate->job_date?->format('Y-m-d')) }}"
                >
            </div>

            <button class="btn btn-secondary" type="submit">
                Save Job Date
            </button>
        </div>

        <div class="help" style="margin-top:8px">
            A morning reminder can be sent on scheduled job days.
        </div>
    </form>
</div>
@endif

@if($estimate->scope_summary)
<div class="card" style="margin-top:20px">
    <div class="kicker">Scope of work</div>
    <p style="white-space:pre-line;line-height:1.6">{{ $estimate->scope_summary }}</p>
</div>
@endif

<div class="card" style="margin-top:20px">
    <div class="kicker">Price</div>
    @foreach($estimate->items as $item)
        <div class="list-row">
            <div>
                <strong>{{ $item->description }}</strong>
                @if($item->details)<div class="muted">{{ $item->details }}</div>@endif
            </div>
            <div class="money">{{ $estimate->money($item->line_total_cents) }}</div>
        </div>
    @endforeach
</div>

@if($estimate->status !== 'draft')
<div class="card" style="margin-top:20px">
    <div class="kicker">Customer link</div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
        <a class="btn btn-primary" href="{{ $estimate->portalUrl() }}" target="_blank" rel="noopener">Open Customer Portal →</a>
        <input style="max-width:520px" value="{{ $estimate->portalUrl() }}" readonly onclick="this.select()">
    </div>
</div>
@endif

@if(in_array($estimate->status, ['deposit_due', 'active_job', 'balance_due'], true) && $estimate->balanceDueCents() > 0)
<div class="card" style="margin-top:20px">
    <div class="kicker">Cash or check</div>
    <h2 style="margin:8px 0">Record an offline payment</h2>
    <p class="muted">Manual payments carry no ContractorSpecialties platform fee.</p>

    @php
        $manualDue = $estimate->status === 'deposit_due'
            ? max(0, $estimate->deposit_cents - $estimate->depositPaidCents())
            : $estimate->balanceDueCents();
    @endphp

    <form method="POST" action="{{ route('payments.manual.estimate', $estimate) }}">
        @csrf
        <div class="grid grid-3">
            <div class="field">
                <label for="method">Method</label>
                <select id="method" name="method">
                    <option value="cash">Cash</option>
                    <option value="check">Check</option>
                </select>
            </div>
            <div class="field">
                <label for="amount">Amount</label>
                <input id="amount" name="amount" type="number" min="0.01" step="0.01" value="{{ number_format($manualDue / 100, 2, '.', '') }}" required>
            </div>
            <div class="field">
                <label for="note">Note</label>
                <input id="note" name="note" placeholder="Check #1042, paid on site…">
            </div>
        </div>
        <button class="btn btn-secondary" type="submit">Record Payment</button>
    </form>
</div>
@endif

@if($estimate->status === 'revision_requested')
<div class="card" style="margin-top:20px;border:2px solid #e7c98d">
    <div class="kicker">Customer wants changes</div>
    @foreach($estimate->revisionRequests as $request)
        <div class="list-row">
            <div>
                <strong>Version {{ $request->estimate_version }}</strong>
                <div class="muted">{{ $request->requested_at->format('M j, Y g:i A') }}</div>
                <p style="margin-bottom:0">{{ $request->message }}</p>
            </div>
        </div>
    @endforeach
</div>
@endif
@endsection
