<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in to ContractorSpecialties</title>
</head>
<body style="margin:0;padding:0;background:#f6f3ee;color:#12233a;font-family:Arial,Helvetica,sans-serif;">
    <div style="padding:36px 16px;">
        <div style="max-width:560px;margin:0 auto;">
            <div style="padding:0 0 22px;text-align:center;">
                <img
                    src="{{ asset('images/contractorspecialties-logo.webp') }}"
                    alt="ContractorSpecialties"
                    width="230"
                    style="display:block;max-width:100%;height:auto;margin:0 auto;"
                >
            </div>

            <div style="background:#ffffff;border:1px solid #d5dce3;border-radius:16px;overflow:hidden;">
                <div style="height:6px;background:#d95b16;"></div>

                <div style="padding:38px 32px;">
                    <p style="margin:0 0 12px;color:#b9440b;font-size:12px;font-weight:bold;letter-spacing:2px;">
                        YOUR SECURE SIGN-IN LINK
                    </p>

                    <h1 style="margin:0 0 18px;color:#12233a;font-size:30px;line-height:1.15;">
                        Let's get back to work.
                    </h1>

                    <p style="margin:0 0 26px;font-size:16px;line-height:1.65;color:#31445a;">
                        Use the button below to securely sign in to your
                        ContractorSpecialties account. No password needed.
                    </p>

                    <div style="text-align:center;margin:28px 0;">
                        <a href="{{ $loginUrl }}"
                           style="display:inline-block;padding:17px 30px;background:#d95b16;color:#ffffff;font-size:16px;font-weight:bold;text-decoration:none;border-radius:9px;">
                            Sign In to ContractorSpecialties
                        </a>
                    </div>

                    <p style="margin:24px 0 0;color:#31445a;font-size:14px;line-height:1.65;">
                        This link expires in <strong>15 minutes</strong> and can only be used once.
                    </p>

                    <p style="margin:18px 0 0;color:#687482;font-size:13px;line-height:1.6;">
                        Didn't request this? You can safely ignore this email.
                    </p>
                </div>
            </div>

            <p style="text-align:center;margin:22px 0 6px;color:#687482;font-size:13px;">
                You know your trade. We'll help with the business part.
            </p>
            <p style="text-align:center;margin:0;color:#687482;font-size:12px;">
                ContractorSpecialties
            </p>
        </div>
    </div>
</body>
</html>
