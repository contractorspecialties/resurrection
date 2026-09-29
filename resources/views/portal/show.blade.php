<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $estimate->estimate_number }} — {{ $estimate->company->name }}</title>
    <style>
        :root{--ink:#12233a;--muted:#687482;--orange:#d95b16;--paper:#f6f3ee;--line:#dde3e8;--success:#176c45;--warn:#8d5b12}
        *{box-sizing:border-box}
        body{margin:0;background:var(--paper);color:var(--ink);font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
        .wrap{width:min(860px,calc(100% - 28px));margin:0 auto;padding:34px 0 60px}
        .card{background:#fff;border:1px solid var(--line);border-radius:18px;padding:26px;box-shadow:0 12px 36px rgba(18,35,58,.06);margin-bottom:18px}
        .top{display:flex;justify-content:space-between;gap:24px;align-items:flex-start}
        .company{font-size:1.35rem;font-weight:950}.muted{color:var(--muted)}
        .kicker{text-transform:uppercase;letter-spacing:.14em;font-weight:900;font-size:.74rem;color:var(--orange)}
        .badge{display:inline-flex;padding:6px 9px;border-radius:999px;background:#eef1f4;font-size:.75rem;text-transform:uppercase;letter-spacing:.08em;font-weight:900}
        h1{font-size:clamp(2.2rem,8vw,4.6rem);line-height:.93;letter-spacing:-.05em;margin:8px 0 12px;text-transform:uppercase}
        h2{margin:0 0 12px}p{line-height:1.6}
        .list-row{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;padding:16px 0;border-bottom:1px solid var(--line)}
        .list-row:last-child{border-bottom:0}.money{font-variant-numeric:tabular-nums;font-weight:900;white-space:nowrap}
        .total{font-size:2rem;font-weight:950}
        .btn{display:inline-flex;align-items:center;justify-content:center;min-height:50px;padding:0 20px;border-radius:8px;border:1px solid transparent;font-weight:900;cursor:pointer}
        .btn-primary{background:var(--orange);color:#fff}.btn-secondary{background:#fff;border-color:#9aa5b1;color:var(--ink)}
        input,textarea{width:100%;border:1px solid #bdc7d0;border-radius:8px;padding:12px 13px;font:inherit}textarea{min-height:120px;resize:vertical}
        label{display:block;font-weight:800;margin-bottom:7px}.field{margin-bottom:16px}
        .status{background:#eef8f2;border:1px solid #b9dfc8;color:var(--success);padding:14px 16px;border-radius:10px;margin-bottom:18px;font-weight:800}
        .warning{background:#fff7e8;border:1px solid #e7c98d;color:var(--warn);padding:14px 16px;border-radius:10px}
        .actions{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        @media(max-width:700px){.top,.actions{grid-template-columns:1fr;display:grid}}
    </style>
</head>
<body>
<div class="wrap">
    @if(session('portal_status'))<div class="status">{{ session('portal_status') }}</div>@endif

    @if($errors->any())
        <div class="warning" style="margin-bottom:18px">
            <strong>Please fix this:</strong>
            <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card">
        <div class="top">
            <div>
                <div class="company">{{ $estimate->company->name }}</div>
                <div class="muted">{{ $estimate->company->trade }} • {{ $estimate->company->city }}, {{ $estimate->company->state }}</div>
            </div>
            <div style="text-align:right">
                <span class="badge">{{ str_replace('_', ' ', $estimate->status) }}</span>
                <div class="muted" style="margin-top:7px">{{ $estimate->estimate_number }} • v{{ $estimate->version }}</div>
            </div>
        </div>

        <div style="margin-top:28px">
            <div class="kicker">Estimate for {{ $estimate->client->name }}</div>
            <h1>{{ $estimate->money($estimate->total_cents) }}</h1>
            <div class="muted">Review the work and price below.</div>
        </div>
    </div>

    @if($estimate->scope_summary)
        <div class="card"><div class="kicker">Scope of work</div><p style="white-space:pre-line">{{ $estimate->scope_summary }}</p></div>
    @endif

    <div class="card">
        <div class="kicker">Price</div>
        @foreach($estimate->items as $item)
            <div class="list-row">
                <div>
                    <strong>{{ $item->description }}</strong>
                    @if($item->details)<div class="muted">{{ $item->details }}</div>@endif
                    <div class="muted">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }} × {{ $estimate->money($item->unit_price_cents) }}</div>
                </div>
                <div class="money">{{ $estimate->money($item->line_total_cents) }}</div>
            </div>
        @endforeach

        <div style="max-width:360px;margin-left:auto;padding-top:12px">
            <div class="list-row"><span>Subtotal</span><span class="money">{{ $estimate->money($estimate->subtotal_cents) }}</span></div>
            @if($estimate->tax_cents > 0)<div class="list-row"><span>Tax</span><span class="money">{{ $estimate->money($estimate->tax_cents) }}</span></div>@endif
            <div class="list-row"><strong>Total</strong><strong class="total">{{ $estimate->money($estimate->total_cents) }}</strong></div>
        </div>
    </div>

    @if($estimate->notes)
        <div class="card"><div class="kicker">Notes / terms</div><p style="white-space:pre-line">{{ $estimate->notes }}</p></div>
    @endif

    @if($estimate->status === 'sent')
        <div class="actions">
            <div class="card">
                <div class="kicker">Need something changed?</div>
                <h2>Ask for a revision</h2>
                <form method="POST" action="{{ route('portal.revision', $estimate->portal_token) }}">
                    @csrf
                    <div class="field"><label for="message">What should change?</label><textarea id="message" name="message" required>{{ old('message') }}</textarea></div>
                    <button class="btn btn-secondary" type="submit">Request Changes</button>
                </form>
            </div>

            <div class="card">
                <div class="kicker">Ready to move forward?</div>
                <h2>Accept this estimate</h2>
                <form method="POST" action="{{ route('portal.accept', $estimate->portal_token) }}">
                    @csrf
                    <div class="field"><label for="signature_name">Type your full name</label><input id="signature_name" name="signature_name" value="{{ old('signature_name') }}" required></div>
                    <label style="display:flex;gap:10px;align-items:flex-start;font-weight:700;margin-bottom:18px">
                        <input style="width:auto;margin-top:4px" type="checkbox" name="agree" value="1" required>
                        <span>I reviewed this estimate and agree to the scope, price and terms shown here.</span>
                    </label>
                    <button class="btn btn-primary" type="submit">Accept Estimate →</button>
                </form>
            </div>
        </div>
    @elseif($estimate->status === 'revision_requested')
        <div class="card"><div class="warning"><strong>Changes requested.</strong><br>Your contractor has your revision request.</div></div>
    @elseif($estimate->status === 'deposit_due')
        <div class="card">
            <div class="kicker">Accepted</div>
            <h2>Deposit due: {{ $estimate->money($estimate->deposit_cents - $estimate->depositPaidCents()) }}</h2>
            @if($estimate->company->hasHealthyStripeConnection())
                <p class="muted">Pay securely through Stripe.</p>
                <form method="POST" action="{{ route('portal.deposit', $estimate->portal_token) }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Pay Deposit →</button>
                </form>
            @else
                <div class="warning">Online payment setup is not ready yet. Please contact {{ $estimate->company->name }}.</div>
            @endif
        </div>
    @elseif($estimate->status === 'active_job')
        <div class="card"><div class="status" style="margin:0">Deposit received. Work is active.</div></div>
    @elseif($estimate->status === 'balance_due')
        <div class="card">
            <div class="kicker">Work complete</div>
            <h2>Final balance due: {{ $estimate->money($estimate->balanceDueCents()) }}</h2>
            @if($estimate->company->hasHealthyStripeConnection())
                <form method="POST" action="{{ route('portal.final-balance', $estimate->portal_token) }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Pay Final Balance →</button>
                </form>
            @else
                <div class="warning">Online payment setup is not ready yet. Please contact {{ $estimate->company->name }}.</div>
            @endif
        </div>
    @elseif($estimate->status === 'paid')
        <div class="card"><div class="status" style="margin:0">Paid in full. Thank you.</div></div>
    @endif

    @if($estimate->accepted_at)
        <div class="card">
            <div class="kicker">Acceptance recorded</div>
            <p>Accepted {{ $estimate->accepted_at->format('M j, Y g:i A') }}. The exact accepted version was preserved.</p>
        </div>
    @endif
</div>
</body>
</html>
