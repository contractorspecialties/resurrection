<!DOCTYPE html>
<html lang="en">
<body style="margin:0;background:#f6f3ee;font-family:Arial,sans-serif;color:#12233a">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f3ee;padding:32px 12px">
<tr><td align="center">
<table role="presentation" width="620" cellpadding="0" cellspacing="0" style="max-width:620px;background:#ffffff;border-radius:14px">
<tr><td style="padding:32px">
    <div style="font-size:13px;font-weight:bold;color:#d95b16;text-transform:uppercase;letter-spacing:1px">
        {{ $company->name }}
    </div>

    <div style="font-size:16px;line-height:1.7;color:#35465a;margin-top:18px">
        {!! nl2br(e($message)) !!}
    </div>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
