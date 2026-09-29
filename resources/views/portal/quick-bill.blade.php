<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $quickBill->quick_bill_number }} — {{ $quickBill->company->name }}</title>
    <style>
        body{margin:0;background:#f6f3ee;color:#12233a;font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
        .wrap{width:min(720px,calc(100% - 28px));margin:50px auto}
        .card{background:#fff;border:1px solid #dde3e8;border-radius:18px;padding:30px;box-shadow:0 12px 36px rgba(18,35,58,.06)}
        .kicker{text-transform:uppercase;letter-spacing:.14em;font-weight:900;font-size:.74rem;color:#d95b16}
        h1{font-size:clamp(2.4rem,9vw,5rem);line-height:.94;letter-spacing:-.05em;margin:10px 0;text-transform:uppercase}
        h2{margin:10px 0}
        p{line-height:1.6;color:#687482}
        .btn{display:inline-flex;align-items:center;justify-content:center;min-height:52px;padding:0 22px;border:0;border-radius:8px;background:#d95b16;color:#fff;font-weight:900;cursor:pointer}
        .status{background:#eef8f2;border:1px solid #b9dfc8;color:#176c45;padding:16px;border-radius:10px;font-weight:800}
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <div class="kicker">{{ $quickBill->company->name }} • {{ $quickBill->quick_bill_number }}</div>
        <h2>{{ $quickBill->description }}</h2>

        @if($quickBill->details)
            <p>{{ $quickBill->details }}</p>
        @endif

        <h1>{{ $quickBill->money($quickBill->amount_cents) }}</h1>

        @if($quickBill->status === 'paid')
            <div class="status">Paid. You’re all set.</div>
        @elseif($quickBill->company->hasHealthyStripeConnection())
            <p>Pay securely through Stripe. ContractorSpecialties does not store your card number.</p>
            <form method="POST" action="{{ route('portal.quick-bill.pay', $quickBill->portal_token) }}">
                @csrf
                <button class="btn" type="submit">Pay {{ $quickBill->money($quickBill->balanceDueCents()) }} →</button>
            </form>
        @else
            <p>Online payment is temporarily unavailable. Please contact {{ $quickBill->company->name }}.</p>
        @endif
    </div>
</div>
</body>
</html>
