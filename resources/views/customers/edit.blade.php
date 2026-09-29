@extends('layouts.app')

@section('title', $client->name . ' — ContractorSpecialties')

@section('content')
<div class="page-head">
    <div>
        <div class="kicker">Customer</div>
        <h1>{{ $client->name }}</h1>
    </div>
</div>

<form method="POST" action="{{ route('customers.update', $client) }}">
    @csrf
    @method('PUT')

<div class="grid grid-2">
    <div class="card">
        <div class="field">
            <label for="name">Customer name</label>
            <input id="name" name="name" value="{{ old('name', $client->name ?? '') }}" required>
        </div>
        <div class="field">
            <label for="company_name">Company name</label>
            <input id="company_name" name="company_name" value="{{ old('company_name', $client->company_name ?? '') }}">
        </div>
        <div class="field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $client->email ?? '') }}">
        </div>
        <div class="field">
            <label for="phone">Phone</label>
            <input id="phone" name="phone" value="{{ old('phone', $client->phone ?? '') }}">
        </div>
    </div>

    <div class="card">
        <div class="field">
            <label for="address_line1">Address</label>
            <input id="address_line1" name="address_line1" value="{{ old('address_line1', $client->address_line1 ?? '') }}">
        </div>
        <div class="field">
            <label for="address_line2">Address line 2</label>
            <input id="address_line2" name="address_line2" value="{{ old('address_line2', $client->address_line2 ?? '') }}">
        </div>
        <div class="grid grid-3">
            <div class="field">
                <label for="city">City</label>
                <input id="city" name="city" value="{{ old('city', $client->city ?? '') }}">
            </div>
            <div class="field">
                <label for="state">State</label>
                <input id="state" name="state" value="{{ old('state', $client->state ?? '') }}">
            </div>
            <div class="field">
                <label for="postal_code">ZIP</label>
                <input id="postal_code" name="postal_code" value="{{ old('postal_code', $client->postal_code ?? '') }}">
            </div>
        </div>
        <div class="field">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes">{{ old('notes', $client->notes ?? '') }}</textarea>
        </div>
    </div>
</div>

    <div style="margin-top:20px;display:flex;gap:10px;flex-wrap:wrap">
        <button class="btn btn-primary" type="submit">Save Changes</button>
    </div>
</form>

<div class="card" style="margin-top:20px;border:1px solid #d8b2ad">
    <div class="kicker">Remove from active customers</div>
    <h2 style="margin:8px 0">Delete if unused. Archive if history exists.</h2>
    <p class="muted">Estimates, acceptances, Quick Bills and payment history are never orphaned just to clean up a customer list.</p>

    <form method="POST" action="{{ route('customers.destroy', $client) }}" onsubmit="return confirm('Remove this customer from the active list?');">
        @csrf
        @method('DELETE')
        <button class="btn btn-danger" type="submit">Remove Customer</button>
    </form>
</div>
@endsection
