@extends('layouts.app')

@section('title', 'Log In — ContractorSpecialties')

@section('content')
<div style="max-width:560px;margin:0 auto">
    <div class="page-head">
        <div>
            <div class="kicker">Welcome back</div>
            <h1>Get back to work.</h1>
            <p>Log in and pick up where you left off.</p>
        </div>
    </div>

    <div class="card">
        <form method="POST" action="{{ route('login.store') }}">
            @csrf

            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" autocomplete="current-password" required>
            </div>

            <div class="field">
                <label style="display:flex;align-items:center;gap:10px;font-weight:700">
                    <input style="width:auto" type="checkbox" name="remember" value="1">
                    Keep me logged in
                </label>
            </div>

            <button class="btn btn-primary" type="submit">Log In →</button>
        </form>
    </div>
</div>
@endsection
