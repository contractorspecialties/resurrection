@extends('layouts.app')

@section('title', ($client->exists ? 'Edit Customer' : 'Add Customer') . ' — ContractorSpecialties')

@section('content')
<div style="max-width:820px;margin:0 auto">
    <div class="page-head">
        <div>
            <div class="kicker">Customers</div>
            <h1>{{ $client->exists ? 'Update customer.' : 'Add a customer.' }}</h1>
            <p>Just the useful stuff. You can always fill in more later.</p>
        </div>
    </div>

    <div class="card">
        <form method="POST" action="{{ $client->exists ? route('customers.update', $client) : route('customers.store') }}">
            @csrf
            @if($client->exists)
                @method('PUT')
            @endif

            <div class="field">
                <label for="name">Customer name</label>
                <input id="name" name="name" value="{{ old('name', $client->name) }}" required autofocus>
            </div>

            <div class="field">
                <label for="company_name">Company name <span class="muted">(optional)</span></label>
                <input id="company_name" name="company_name" value="{{ old('company_name', $client->company_name) }}">
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div class="field">
                    <label for="phone">Phone</label>
                    <input id="phone" name="phone" value="{{ old('phone', $client->phone) }}">
                </div>

                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $client->email) }}">
                </div>
            </div>

            <div class="field">
                <label for="address_line1">Address</label>
                <input id="address_line1" name="address_line1" value="{{ old('address_line1', $client->address_line1) }}">
            </div>

            <div class="field">
                <label for="address_line2">Address line 2</label>
                <input id="address_line2" name="address_line2" value="{{ old('address_line2', $client->address_line2) }}">
            </div>

            <div style="display:grid;grid-template-columns:1fr 150px 150px;gap:16px">
                <div class="field">
                    <label for="city">City</label>
                    <input id="city" name="city" value="{{ old('city', $client->city) }}">
                </div>

                <div class="field">
                    <label for="state">State</label>
                    <input id="state" name="state" value="{{ old('state', $client->state) }}">
                </div>

                <div class="field">
                    <label for="postal_code">ZIP</label>
                    <input id="postal_code" name="postal_code" value="{{ old('postal_code', $client->postal_code) }}">
                </div>
            </div>

            <div class="field">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" placeholder="Gate code, dog’s name, how they found you, anything useful…">{{ old('notes', $client->notes) }}</textarea>
            </div>

            <div style="display:flex;gap:10px;flex-wrap:wrap">
                <button class="btn btn-primary" type="submit">
                    {{ $client->exists ? 'Save Customer' : 'Add Customer' }}
                </button>

                <a class="btn btn-secondary" href="{{ route('customers.index') }}">Cancel</a>
            </div>
        </form>

        @if($client->exists)
            <form method="POST" action="{{ route('customers.destroy', $client) }}" style="margin-top:28px;padding-top:22px;border-top:1px solid var(--line)" onsubmit="return confirm('Remove this customer?');">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger" type="submit">Remove Customer</button>
            </form>
        @endif
    </div>
</div>
@endsection
