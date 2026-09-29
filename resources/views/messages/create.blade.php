@extends('layouts.app')

@section('title', 'Send Message — ContractorSpecialties')

@section('content')
<div class="page-head">
    <div>
        <div class="kicker">Messages</div>
        <h1>Send the customer a note.</h1>
        <p>Use the estimate or Quick Bill screens when you want to send those exact links. This is for normal customer communication.</p>
    </div>
</div>

@if($clients->isEmpty())
    <div class="card">
        <h2>No active customers yet.</h2>
        <p class="muted">Add a customer before trying to message one.</p>
        <a class="btn btn-primary" href="{{ route('customers.create') }}">+ Customer</a>
    </div>
@else
<form method="POST" action="{{ route('messages.store') }}">
    @csrf

    <div class="grid grid-2">
        <div class="card">
            <div class="field">
                <label for="client_id">Customer</label>
                <select id="client_id" name="client_id" required>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" @selected((string) old('client_id') === (string) $client->id)>
                            {{ $client->name }}
                            @if($client->email || $client->phone)
                                — {{ $client->email ?: $client->phone }}
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="channel">Send by</label>
                <select id="channel" name="channel" required>
                    <option value="email" @selected(old('channel') === 'email')>Email</option>
                    <option value="sms" @selected(old('channel') === 'sms')>SMS</option>
                </select>
                <div class="help">SMS requires a healthy integrated payment connection.</div>
            </div>

            <div class="field" id="subject-field">
                <label for="subject">Email subject</label>
                <input id="subject" name="subject" value="{{ old('subject') }}" placeholder="A quick note from us">
            </div>
        </div>

        <div class="card">
            <div class="field">
                <label for="message">Message</label>
                <textarea id="message" name="message" required placeholder="Hi John — we’ll be there around 9 tomorrow morning.">{{ old('message') }}</textarea>
            </div>

            <p class="muted">
                Keep SMS short. ContractorSpecialties automatically prefixes text messages with your business name.
            </p>
        </div>
    </div>

    <div style="margin-top:20px">
        <button class="btn btn-primary" type="submit">Send Message →</button>
    </div>
</form>
@endif
@endsection

@push('scripts')
<script>
(() => {
    const channel = document.getElementById('channel');
    const subjectField = document.getElementById('subject-field');

    if (!channel || !subjectField) return;

    function update() {
        subjectField.style.display = channel.value === 'email' ? 'block' : 'none';
    }

    channel.addEventListener('change', update);
    update();
})();
</script>
@endpush
