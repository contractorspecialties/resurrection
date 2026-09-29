@extends('layouts.app')

@section('title', 'Quick Bills — ContractorSpecialties')

@section('content')
<div class="page-head">
    <div>
        <div class="kicker">Quick Bill</div>
        <h1>The “while you’re here” button.</h1>
        <p>Price the little add-on, collect it, then fix the plumb bob.</p>
    </div>

    <a class="btn btn-primary" href="{{ route('quick-bills.create') }}">+ Quick Bill</a>
</div>

<div class="card">
    @forelse($quickBills as $quickBill)
        <div class="list-row">
            <div>
                <strong>{{ $quickBill->quick_bill_number }} — {{ $quickBill->client->name }}</strong>
                <div class="muted">{{ $quickBill->description }} • <span class="badge">{{ str_replace('_', ' ', $quickBill->status) }}</span></div>
            </div>

            <div style="display:flex;gap:14px;align-items:center">
                <span class="money">{{ $quickBill->money($quickBill->amount_cents) }}</span>
                <a class="btn btn-secondary" href="{{ route('quick-bills.show', $quickBill) }}">Open</a>
            </div>
        </div>
    @empty
        <div class="empty">
            No plumb bobs have required emergency financial intervention yet.
        </div>
    @endforelse
</div>
@endsection
