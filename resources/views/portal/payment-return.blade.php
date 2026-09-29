<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment submitted — {{ $estimate->company->name }}</title>
    <style>
        body{margin:0;background:#f6f3ee;color:#12233a;font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
        .wrap{width:min(680px,calc(100% - 28px));margin:70px auto}
        .card{background:#fff;border:1px solid #dde3e8;border-radius:18px;padding:32px;box-shadow:0 12px 36px rgba(18,35,58,.06)}
        .kicker{text-transform:uppercase;letter-spacing:.14em;font-weight:900;font-size:.74rem;color:#d95b16}
        h1{font-size:clamp(2.2rem,8vw,4.4rem);line-height:.94;letter-spacing:-.05em;text-transform:uppercase;margin:10px 0}
        p{line-height:1.6;color:#687482}
        a{display:inline-flex;align-items:center;justify-content:center;min-height:50px;padding:0 20px;border-radius:8px;background:#d95b16;color:#fff;text-decoration:none;font-weight:900}
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <div class="kicker">Payment submitted</div>

        @if($payment?->status === 'paid')
            <h1>Payment received.</h1>
            <p>Your payment has been confirmed. Thank you.</p>
        @else
            <h1>We’re confirming it.</h1>
            <p>
                Stripe returned you to the estimate successfully. ContractorSpecialties waits for Stripe’s signed server confirmation before marking money paid.
            </p>
        @endif

        <a href="{{ route('portal.show', $estimate->portal_token) }}">Return to Estimate →</a>
    </div>
</div>
</body>
</html>
