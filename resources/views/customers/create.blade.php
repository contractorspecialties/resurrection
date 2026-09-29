@extends('layouts.app')

@section('title', 'New Customer — ContractorSpecialties')

@section('content')
<div class="page-head">
    <div>
        <div class="kicker">Customers</div>
        <h1>Add customer.</h1>
    </div>
</div>

<form method="POST" action="{{ route('customers.store') }}">
    @csrf

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

    <div style="margin-top:20px">
        <button class="btn btn-primary" type="submit">Add Customer →</button>
    </div>
</form>
@endsection
