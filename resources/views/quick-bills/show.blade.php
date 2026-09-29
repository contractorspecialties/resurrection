@extends('layouts.app')

@section('title', $quickBill->quick_bill_number . ' — ContractorSpecialties')

@section('content')

@include('quick-bills._message-actions')
<div class="page-head">
    <div>
        <div class="kicker">{{ $quickBill->quick_bill_number }}</div>
        <h1>{{ $quickBill->client->name }}</h1>
        <p><span class="badge">{{ str_replace('_', ' ', $quickBill->status) }}</span></p>
    </div>
</div>

<div class="grid grid-2">
    <div class="card">
        <div class="kicker">The add-on</div>
        <h2 style="margin:8px 0">{{ $quickBill->description }}</h2>
        @if($quickBill->details)
            <p class="muted">{{ $quickBill->details }}</p>
        @endif
    </div>

    <div class="card">
        <div class="kicker">Amount</div>
        <div class="big-number">{{ $quickBill->money($quickBill->amount_cents) }}</div>
        @if($quickBill->paid_at)
            <p class="muted">Paid {{ $quickBill->paid_at->format('M j, Y g:i A') }}</p>
        @endif
    </div>
</div>

<div class="card" style="margin-top:20px">
    <div class="kicker">Customer payment link</div>
    <h2 style="margin:8px 0">Hand them the phone, text it later, or copy the link.</h2>

    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
        <a class="btn btn-primary" target="_blank" rel="noopener" href="{{ $quickBill->portalUrl() }}">Open Quick Bill →</a>
        <input style="max-width:520px" value="{{ $quickBill->portalUrl() }}" readonly onclick="this.select()">
    </div>
</div>

@if($quickBill->status === 'payment_due')
<div class="card" style="margin-top:20px">
    <div class="kicker">Cash or check</div>
    <h2 style="margin:8px 0">Record an offline payment</h2>
    <p class="muted">Manual payments carry no ContractorSpecialties platform fee.</p>

    <form method="POST" action="{{ route('payments.manual.quick-bill', $quickBill) }}">
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
                <input id="amount" name="amount" type="number" min="0.01" step="0.01" value="{{ number_format($quickBill->balanceDueCents() / 100, 2, '.', '') }}" required>
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
@endsection
