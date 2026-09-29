@extends('layouts.app')

@section('title', 'Customers — ContractorSpecialties')

@section('content')
<div class="page-head">
    <div>
        <div class="kicker">Customers</div>
        <h1>Your people.</h1>
        <p>Keep the useful stuff close. Archive history instead of deleting it.</p>
    </div>

    <div style="display:flex;gap:10px;flex-wrap:wrap">
        @if($archivedCount)
            <a class="btn btn-secondary" href="{{ route('customers.archived') }}">Archived ({{ $archivedCount }})</a>
        @endif
        <a class="btn btn-primary" href="{{ route('customers.create') }}">+ Customer</a>
    </div>
</div>

<div class="card">
    @forelse($clients as $client)
        <div class="list-row">
            <div>
                <strong>{{ $client->name }}</strong>
                <div class="muted">
                    {{ $client->company_name ?: ($client->phone ?: ($client->email ?: 'No contact info yet')) }}
                </div>
            </div>
            <a class="btn btn-secondary" href="{{ route('customers.edit', $client) }}">Open</a>
        </div>
    @empty
        <div class="empty">No customers yet.</div>
    @endforelse
</div>
@endsection
