<!DOCTYPE html>
<html lang="en">
<body style="margin:0;background:#f6f3ee;font-family:Arial,sans-serif;color:#12233a">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f3ee;padding:32px 12px">
<tr><td align="center">
<table role="presentation" width="620" cellpadding="0" cellspacing="0" style="max-width:620px;background:#ffffff;border-radius:14px">
<tr><td style="padding:32px">
    <div style="font-size:13px;font-weight:bold;color:#d95b16;text-transform:uppercase;letter-spacing:1px">
        {{ $estimate->company->name }}
    </div>

    <h1 style="margin:12px 0 8px;font-size:30px">Your estimate is ready.</h1>

    <p style="font-size:16px;line-height:1.6;color:#5e6a78">
        Review the scope, price and terms for {{ $estimate->estimate_number }}.
        You can request changes or accept the estimate from the secure customer page.
    </p>

    <p style="font-size:24px;font-weight:bold;margin:24px 0">
        {{ $estimate->money($estimate->total_cents) }}
    </p>

    <p style="margin:28px 0">
        <a href="{{ $estimate->portalUrl() }}"
           style="display:inline-block;background:#d95b16;color:#ffffff;text-decoration:none;padding:14px 20px;border-radius:7px;font-weight:bold">
            Review Estimate →
        </a>
    </p>

    <p style="font-size:13px;color:#7a8490;line-height:1.5">
        If the button does not work, copy and paste this address:<br>
        {{ $estimate->portalUrl() }}
    </p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
