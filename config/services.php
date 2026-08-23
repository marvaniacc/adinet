<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'),
    ],

    'kavenegar' => [
        'key' => env('KAVENEGAR_API_KEY'),
        'sender' => env('KAVENEGAR_SENDER'),
        // OTP template approved in the Kavenegar panel containing: کد ورود: %token
        // When set, OTPs go through Verify-Lookup; otherwise generic SMS.
        'otp_template' => env('KAVENEGAR_OTP_TEMPLATE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payments - ZarinPal
    |--------------------------------------------------------------------------
    |
    | mode: fake (in-app simulation, no external calls) | sandbox | live
    | merchant_id: your ZarinPal merchant ID (required for sandbox/live).
    |
    */

    'zarinpal' => [
        'mode' => env('ZARINPAL_MODE', 'fake'),
        'merchant_id' => env('ZARINPAL_MERCHANT_ID', ''),
    ],

];
