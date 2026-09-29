<div class="card" style="margin-top:20px">
    <div class="kicker">Send to customer</div>
    <h2 style="margin:8px 0">Get it off your screen and onto theirs.</h2>

    <div style="display:flex;gap:10px;flex-wrap:wrap">
        @if($estimate->client->email)
            <form method="POST" action="{{ route('messages.estimate.email', $estimate) }}">
                @csrf
                <button class="btn btn-primary" type="submit">Email Estimate</button>
            </form>
        @else
            <span class="muted">No customer email.</span>
        @endif

        @if($estimate->client->phone)
            <form method="POST" action="{{ route('messages.estimate.sms', $estimate) }}">
                @csrf
                <button class="btn btn-secondary" type="submit"
                    @disabled(! $estimate->company->hasHealthyStripeConnection())>
                    Text Estimate
                </button>
            </form>
        @else
            <span class="muted">No customer phone.</span>
        @endif
    </div>

    @if($estimate->client->phone && ! $estimate->company->hasHealthyStripeConnection())
        <p class="help">SMS unlocks after the contractor has a healthy integrated payment connection.</p>
    @endif
</div>
