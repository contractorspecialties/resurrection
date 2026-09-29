@extends('layouts.app')

@section('title', 'Archived Customers — ContractorSpecialties')

@section('content')
<div class="page-head">
    <div>
        <div class="kicker">Customers</div>
        <h1>Archived.</h1>
        <p>Out of the daily workflow, but their history is still intact.</p>
    </div>
    <a class="btn btn-secondary" href="{{ route('customers.index') }}">← Active Customers</a>
</div>

<div class="card">
    @forelse($clients as $client)
        <div class="list-row">
            <div>
                <strong>{{ $client->name }}</strong>
                <div class="muted">Archived {{ $client->archived_at?->format('M j, Y') }}</div>
            </div>

            <form method="POST" action="{{ route('customers.restore', $client->id) }}">
                @csrf
                <button class="btn btn-secondary" type="submit">Restore</button>
            </form>
        </div>
    @empty
        <div class="empty">Nobody in the witness protection program.</div>
    @endforelse
</div>
@endsection
