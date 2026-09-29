<!DOCTYPE html>
<html lang="en">
<body style="margin:0;background:#f6f3ee;font-family:Arial,sans-serif;color:#12233a">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f3ee;padding:32px 12px">
<tr><td align="center">
<table role="presentation" width="620" cellpadding="0" cellspacing="0" style="max-width:620px;background:#ffffff;border-radius:14px">
<tr><td style="padding:32px">
    <div style="font-size:13px;font-weight:bold;color:#d95b16;text-transform:uppercase;letter-spacing:1px">
        {{ $quickBill->company->name }}
    </div>

    <h1 style="margin:12px 0 8px;font-size:30px">{{ $quickBill->description }}</h1>

    <p style="font-size:16px;line-height:1.6;color:#5e6a78">
        {{ $quickBill->quick_bill_number }} is ready for payment.
    </p>

    <p style="font-size:32px;font-weight:bold;margin:24px 0">
        {{ $quickBill->money($quickBill->balanceDueCents()) }}
    </p>

    <p style="margin:28px 0">
        <a href="{{ $quickBill->portalUrl() }}"
           style="display:inline-block;background:#d95b16;color:#ffffff;text-decoration:none;padding:14px 20px;border-radius:7px;font-weight:bold">
            View & Pay →
        </a>
    </p>

    <p style="font-size:13px;color:#7a8490;line-height:1.5">
        If the button does not work, copy and paste this address:<br>
        {{ $quickBill->portalUrl() }}
    </p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
