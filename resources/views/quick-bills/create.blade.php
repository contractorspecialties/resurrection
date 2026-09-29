@extends('layouts.app')

@section('title', 'New Quick Bill — ContractorSpecialties')

@section('content')
<div class="page-head">
    <div>
        <div class="kicker">Quick Bill</div>
        <h1>Price it. Get paid. Do it.</h1>
        <p>For the customer who says, “While you’re here…”</p>
    </div>
</div>

<form method="POST" action="{{ route('quick-bills.store') }}">
    @csrf

    <div class="grid grid-2">
        <div class="card">
            <div class="field">
                <label for="client_id">Customer</label>
                <select id="client_id" name="client_id" required>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" @selected((string) old('client_id') === (string) $client->id)>
                            {{ $client->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="description">What are you fixing?</label>
                <input id="description" name="description" value="{{ old('description') }}" required placeholder="Replace leaking hose bib">
            </div>

            <div class="field">
                <label for="details">Details <span class="muted">(optional)</span></label>
                <textarea id="details" name="details" placeholder="Includes new frost-proof sillcock and cleanup.">{{ old('details') }}</textarea>
            </div>
        </div>

        <div class="card">
            <div class="field">
                <label for="amount">Agreed price</label>
                <input id="amount" name="amount" type="number" min="0.01" step="0.01" value="{{ old('amount') }}" required>
            </div>

            <div style="padding:18px;background:#f7f3ed;border-radius:12px">
                <div class="kicker">Rule</div>
                <h2 style="margin:8px 0">Get paid before doing the add-on.</h2>
                <p class="muted">Quick Bill is deliberately separate from rewriting the original accepted estimate.</p>
            </div>
        </div>
    </div>

    <div style="margin-top:20px">
        <button class="btn btn-primary" type="submit">Create Quick Bill →</button>
    </div>
</form>
@endsection
