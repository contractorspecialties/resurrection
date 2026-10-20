@extends('layouts.app')

@section('title', 'Get Started — ContractorSpecialties')

@section('content')
<div style="max-width:620px;margin:0 auto">
    <div class="page-head">
        <div>
            <div class="kicker">Start free</div>
            <h1>Let’s get your business set up.</h1>
            <p>No paperwork marathon. Create your login, then tell us a little about your business. You can get to work without setting everything up at once.</p>
        </div>
    </div>

    <div class="card">
        <h2 style="margin:0 0 12px">What are you signing up for?</h2>
        <p>Simple tools for keeping customers organized, building estimates, tracking jobs, and getting paid. No credit card required to create your account.</p>
        <p><strong>What's the catch?</strong> There isn't a required subscription. On Pay as You Go, ContractorSpecialties earns $2 per eligible integrated online payment, capped at $5 per estimate or project. Stripe's processing fees are separate. Prefer a flat bill? Monthly is $12.95 with no additional ContractorSpecialties transaction fees during paid coverage.</p>
        <p><strong>What happens next?</strong> We'll ask what kind of work you do and where you do it. You can add your first customer and estimate without connecting a payment account. When you want to collect online payments, we'll walk you through connecting Stripe.</p>
        <hr style="margin:20px 0">

        <div class="kicker">Your pricing options</div>
        <h2 style="margin:8px 0 12px">Same tools. Your choice.</h2>

        <div class="grid grid-2" style="margin-bottom:16px">
            <div class="card" style="padding:16px">
                <strong>Pay as You Go</strong>
                <div class="big-number" style="margin:8px 0">$0/month</div>
                <p class="muted">$2 per eligible integrated online payment, capped at $5 per estimate or project.</p>
                <p><strong>Included automatically when you sign up.</strong></p>
            </div>
            <div class="card" style="padding:16px">
                <strong>Monthly</strong>
                <div class="big-number" style="margin:8px 0">$12.95/month</div>
                <p class="muted">No additional ContractorSpecialties transaction fees while paid. Stripe processing fees are separate.</p>
                <p><strong>Available to activate separately after launch.</strong></p>
            </div>
        </div>

        <p class="muted">
            Create your free account below. No subscription is started
            and no payment information is collected on this page.
        </p>

        <hr style="margin:20px 0">
        <form method="POST" action="{{ route('register.store') }}">
            @csrf

            <div class="field">
                <label for="name">Your name</label>
                <input id="name" name="name" value="{{ old('name') }}" autocomplete="name" required autofocus>
            </div>

            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" autocomplete="new-password" required>
                <div class="help">At least 8 characters.</div>
            </div>

            <div class="field">
                <label for="password_confirmation">Confirm password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>
            </div>

            <button class="btn btn-primary" type="submit">Create My Free Account →</button>
        </form>
    </div>
</div>
@endsection
