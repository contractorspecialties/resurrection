@extends('layouts.app')

@section('title', 'Confirm Sign In — ContractorSpecialties')

@section('content')
<div style="max-width:560px;margin:0 auto">
    <div class="page-head">
        <div>
            <div class="kicker">One more click</div>
            <h1>Ready to get back to work?</h1>
            <p>Confirm below to securely sign in.</p>
        </div>
    </div>

    <div class="card">
        <form method="POST"
              action="{{ route('magic-login.consume', ['token' => $token]) }}">
            @csrf
            <button type="submit" class="btn btn-primary">
                Sign In
            </button>
        </form>
    </div>
</div>
@endsection
