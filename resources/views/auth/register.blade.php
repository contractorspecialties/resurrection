@extends('layouts.app')

@section('title', 'Get Started — ContractorSpecialties')

@section('content')
<div style="max-width:620px;margin:0 auto">
    <div class="page-head">
        <div>
            <div class="kicker">Start free</div>
            <h1>Build the business around your work.</h1>
            <p>Create your login first. We’ll set up the business in the next screen.</p>
        </div>
    </div>

    <div class="card">
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

            <button class="btn btn-primary" type="submit">Create My Account →</button>
        </form>
    </div>
</div>
@endsection
