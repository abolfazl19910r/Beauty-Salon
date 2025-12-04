<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'sms' => [
        'api_key' => env('SMS_API_KEY'),
        'base_url' => env('SMS_BASE_URL'),
    ],

    'kavenegar' => [
        'api_key' => env('KAVENEGAR_API_KEY'),
        'sender' => env('KAVENEGAR_SENDER', '2000660110'),
        'send_in_local' => env('KAVENEGAR_SEND_IN_LOCAL', false),

        'templates' => [
            'login_verify' => env('KAVENEGAR_TEMPLATE_LOGIN', 'login-verify'),
            'register_verify' => env('KAVENEGAR_TEMPLATE_REGISTER', 'register-verify'),
            'reset_password' => env('KAVENEGAR_TEMPLATE_RESET', 'reset-password'),
            'two_factor_auth' => env('KAVENEGAR_TEMPLATE_2FA', 'two-factor-auth'),
        ],
    ],

    'zarinpal' => [
        'merchant_id' => env('ZARINPAL_MERCHANT_ID'),
        'api_key' => env('ZARINPAL_API_KEY'),
        'base_url' => env('ZARINPAL_BASE_URL', 'https://api.zarinpal.com/pg/v4'),
        'sandbox' => env('ZARINPAL_SANDBOX', true)
    ],
];
