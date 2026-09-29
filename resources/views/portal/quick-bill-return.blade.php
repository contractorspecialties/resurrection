<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment submitted — {{ $quickBill->company->name }}</title>
    <style>
        body{margin:0;background:#f6f3ee;color:#12233a;font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
        .wrap{width:min(680px,calc(100% - 28px));margin:70px auto}
        .card{background:#fff;border:1px solid #dde3e8;border-radius:18px;padding:32px}
        a{display:inline-flex;padding:14px 20px;border-radius:8px;background:#d95b16;color:#fff;text-decoration:none;font-weight:900}
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        @if($payment?->status === 'paid')
            <h1>Payment received.</h1>
            <p>Your Quick Bill has been paid.</p>
        @else
            <h1>We’re confirming it.</h1>
            <p>Stripe returned successfully. The signed webhook is still the authority on whether money moved.</p>
        @endif

        <a href="{{ route('portal.quick-bill.show', $quickBill->portal_token) }}">Return to Quick Bill →</a>
    </div>
</div>
</body>
</html>
