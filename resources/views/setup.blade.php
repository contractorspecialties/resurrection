@extends('layouts.app')

@section('title', 'Set Up Your Business — ContractorSpecialties')

@section('content')
<div style="max-width:760px;margin:0 auto">
    <div class="page-head">
        <div>
            <div class="kicker">About 60 seconds</div>
            <h1>Let’s set up your business.</h1>
            <p>No corporate interrogation. Just enough to make the software useful.</p>
        </div>
    </div>

    <div class="card">
        <form method="POST" action="{{ route('setup.store') }}">
            @csrf

            <div class="field">
                <label for="name">Your name</label>
                <input id="name" name="name" value="{{ old('name', auth()->user()->name) }}" required>
            </div>

            <div class="field">
                <label for="business_name">Business name</label>
                <input id="business_name" name="business_name" value="{{ old('business_name') }}" placeholder="Leave blank if you don’t have one yet">
                <div class="help">No formal name yet? Fine. We’ll use your name for now.</div>
            </div>

            <div class="field">
                <label for="trade">What kind of work do you do?</label>
                <input id="trade" name="trade" value="{{ old('trade') }}" placeholder="Roofing, handyman, landscaping, cleaning…" required>
            </div>

            <div style="display:grid;grid-template-columns:1fr 180px;gap:16px">
                <div class="field">
                    <label for="city">Base city</label>
                    <input id="city" name="city" value="{{ old('city') }}" required>
                </div>

                <div class="field">
                    <label for="state">State</label>
                    <input id="state" name="state" value="{{ old('state') }}" required>
                </div>
            </div>

            <div class="field">
                <label for="preferred_customer_contact">How do you usually contact customers?</label>
                <select id="preferred_customer_contact" name="preferred_customer_contact" required>
                    <option value="text" @selected(old('preferred_customer_contact', 'text') === 'text')>Text message</option>
                    <option value="email" @selected(old('preferred_customer_contact') === 'email')>Email</option>
                    <option value="phone" @selected(old('preferred_customer_contact') === 'phone')>Phone call</option>
                </select>
            </div>

            <button class="btn btn-primary" type="submit">Enter ContractorSpecialties →</button>
        </form>
    </div>
</div>
@endsection
