<div class="card" style="margin-top:20px">
    <div class="kicker">Send to customer</div>
    <h2 style="margin:8px 0">Get the link in their hand.</h2>

    <div style="display:flex;gap:10px;flex-wrap:wrap">
        @if($quickBill->client->email)
            <form method="POST" action="{{ route('messages.quick-bill.email', $quickBill) }}">
                @csrf
                <button class="btn btn-primary" type="submit">Email Quick Bill</button>
            </form>
        @else
            <span class="muted">No customer email.</span>
        @endif

        @if($quickBill->client->phone)
            <form method="POST" action="{{ route('messages.quick-bill.sms', $quickBill) }}">
                @csrf
                <button class="btn btn-secondary" type="submit"
                    @disabled(! $quickBill->company->hasHealthyStripeConnection())>
                    Text Quick Bill
                </button>
            </form>
        @else
            <span class="muted">No customer phone.</span>
        @endif
    </div>
</div>
