@extends('layouts.app')

@section('title', 'Log In — ContractorSpecialties')

@section('content')
<style>
    .cs-login-intro{max-width:800px;margin:0 auto 30px;text-align:center}
    .cs-login-intro h1{
        margin:8px 0 12px;
        font-size:clamp(2.2rem,5vw,3.6rem);
        line-height:1;
        letter-spacing:-.045em;
        text-transform:uppercase;
    }
    .cs-login-intro p{font-size:1.05rem;color:#46576b;line-height:1.6}
    .cs-login-grid{
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:20px;
        max-width:980px;
        margin:0 auto;
        align-items:stretch;
    }
    .cs-login-card{border:2px solid #d5dce3;padding:30px}
    .cs-login-card.primary{border-top:6px solid #d95b16}
    .cs-login-card h2{font-size:1.55rem;margin:8px 0 12px}
    .cs-login-card p{color:#46576b;line-height:1.6;min-height:55px}
    .cs-login-card .btn{width:100%;min-height:52px;font-size:1rem}
    .cs-login-card input{min-height:50px;font-size:16px}
    .cs-login-note{
        max-width:980px;margin:24px auto 0;
        text-align:center;color:#46576b;font-size:.95rem;
    }
    @media(max-width:760px){
        .cs-login-grid{grid-template-columns:1fr}
        .cs-login-card{padding:24px}
        .cs-login-card p{min-height:0}
    }
</style>

<div class="cs-login-intro">
    <div class="kicker">Welcome back</div>
    <h1>Let's get back to work.</h1>
    <p>Customers, estimates, and getting paid. Pick up right where you left off.</p>
</div>

@if(session('status'))
    <div class="status" style="max-width:980px;margin:0 auto 20px">
        {{ session('status') }}
    </div>
@endif

@if($errors->any())
    <div class="errors" role="alert" style="max-width:980px;margin:0 auto 20px">
        {{ $errors->first() }}
    </div>
@endif

<div class="cs-login-grid">
    <section class="card cs-login-card primary">
        <div class="kicker">Fast & secure</div>
        <h2>Sign in with email</h2>
        <p>We'll send you a secure, one-time link. No password to remember.</p>

        <form method="POST" action="{{ route('magic-login.send') }}">
            @csrf
            <div class="field">
                <label for="magic-email">Email address</label>
                <input id="magic-email" type="email" name="email"
                       value="{{ old('email') }}" autocomplete="email" required>
            </div>
            <button class="btn btn-primary" type="submit">
                Email My Sign-In Link
            </button>
        </form>
    </section>

    <section class="card cs-login-card">
        <div class="kicker">The familiar way</div>
        <h2>Use your password</h2>
        <p>Prefer your usual login? No problem. We've got you covered.</p>

        <form method="POST" action="{{ route('login.store') }}">
            @csrf
            <div class="field">
                <label for="email">Email address</label>
                <input id="email" type="email" name="email"
                       value="{{ old('email') }}" autocomplete="email" required>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" type="password" name="password"
                       autocomplete="current-password" required>
            </div>
            <div class="field">
                <label style="display:flex;align-items:center;gap:10px">
                    <input style="width:22px;height:22px" type="checkbox"
                           name="remember" value="1">
                    Keep me logged in
                </label>
            </div>
            <button class="btn btn-secondary" type="submit">
                Sign In
            </button>
        </form>
    </section>
</div>

<p class="cs-login-note">
    You know your trade. We'll help with the business part.
</p>
@endsection
