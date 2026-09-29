<?php

return [
    'sms' => [
        'provider' => env('SMS_PROVIDER', 'telnyx'),
        'telnyx_api_key' => env('TELNYX_API_KEY'),
        'telnyx_from' => env('TELNYX_FROM_NUMBER'),
        'telnyx_messaging_profile_id' => env('TELNYX_MESSAGING_PROFILE_ID'),
    ],
];
