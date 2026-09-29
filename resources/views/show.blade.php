@extends('layouts.app')

@section('title', 'Estimates — ContractorSpecialties')

@section('content')
<div class="page-head">
    <div>
        <div class="kicker">Estimates</div>
        <h1>Price the work. Win the job.</h1>
        <p>Keep the scope, price and customer in one place instead of reconstructing it from six text messages.</p>
    </div>

    <a class="btn btn-primary" href="{{ route('estimates.create') }}">+ New Estimate</a>
</div>

<div class="card">
    @forelse($estimates as $estimate)
        <div class="list-row">
            <div>
                <strong>{{ $estimate->estimate_number }} — {{ $estimate->client->name }}</strong>
                <div class="muted">
                    {{ $estimate->created_at->format('M j, Y') }} •
                    <span class="badge">{{ $estimate->status }}</span>
                </div>
            </div>

            <div style="display:flex;align-items:center;gap:16px">
                <span class="money">{{ $estimate->money($estimate->total_cents) }}</span>
                <a class="btn btn-secondary" href="{{ route('estimates.show', $estimate) }}">Open</a>
            </div>
        </div>
    @empty
        <div class="empty">
            <p><strong>No estimates yet.</strong></p>
            <p>That’s about to become considerably less true.</p>
            <a class="btn btn-primary" href="{{ route('estimates.create') }}">Create First Estimate</a>
        </div>
    @endforelse

    @if($estimates->hasPages())
        <div style="margin-top:20px">{{ $estimates->links() }}</div>
    @endif
</div>
@endsection
