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
        // ⭐ Fix (test-writing session 7): env('KEY', 'default') only falls back when the
        // key is entirely undefined, not when .env defines it but leaves it empty — and
        // .env.example ships every one of these keys empty. A fresh deployment that
        // copies .env.example without filling these in would silently send real SMS
        // requests to Kavenegar with an empty sender/template name instead of the
        // intended default, rather than falling back safely. Same root cause and fix
        // pattern already applied to SECURITY_LOG_LEVEL/PAYMENTS_LOG_LEVEL in
        // config/logging.php.
        'sender' => env('KAVENEGAR_SENDER') ?: '2000660110',
        'send_in_local' => env('KAVENEGAR_SEND_IN_LOCAL', false),

        'templates' => [
            'login_verify' => env('KAVENEGAR_TEMPLATE_LOGIN') ?: 'login-verify',
            'register_verify' => env('KAVENEGAR_TEMPLATE_REGISTER') ?: 'register-verify',
            'reset_password' => env('KAVENEGAR_TEMPLATE_RESET') ?: 'reset-password',
            'two_factor_auth' => env('KAVENEGAR_TEMPLATE_2FA') ?: 'two-factor-auth',
        ],
    ],

    'zarinpal' => [
        'merchant_id' => env('ZARINPAL_MERCHANT_ID', '279ad21d-99e3-402d-b3c1-2981d375a604'),
        'api_key' => env('ZARINPAL_API_KEY'),
        'base_url' => env('ZARINPAL_BASE_URL', 'https://api.zarinpal.com/pg/v4'),
        'sandbox' => env('ZARINPAL_SANDBOX', true),

        // ⭐ Payout (specialized automatic withdrawal settlement) — intentionally separate from merchant_id/api_key above:
        // ZarrinPal provides a dedicated API Key for the Payout feature (which is only issued by separately activating
        // this feature in the acceptor panel), not the same API Key for regular payments.
        // This sandbox is also kept separate from the regular payments sandbox so that tests of
        // Payout can be performed separately on the sandbox even when the regular payment gateway is in production.
        'payout' => [
            'api_key' => env('ZARINPAL_PAYOUT_API_KEY'),
            'sandbox' => env('ZARINPAL_PAYOUT_SANDBOX', env('ZARINPAL_SANDBOX', true)),
            'base_url' => env('ZARINPAL_PAYOUT_BASE_URL', 'https://api.zarinpal.com/pg/v4'),
            'sandbox_base_url' => env('ZARINPAL_PAYOUT_SANDBOX_BASE_URL', 'https://sandbox.zarinpal.com/pg/v4'),
        ],
    ],

    // ⭐ Wired up (test-writing session 11): TWO_FACTOR_TIMEOUT/TWO_FACTOR_CODE_LENGTH used to be
    // present in .env.example but never actually read anywhere in the app — TwoFactorAuthService
    // hardcoded a 2-minute expiry and a fixed 6-digit code. Both are now read from here, with the
    // exact same effective defaults so existing behavior/tests are unchanged unless an operator
    // explicitly sets these in .env.
    'two_factor' => [
        'timeout_minutes' => (int) (env('TWO_FACTOR_TIMEOUT') ?: 2),
        'code_length' => (int) (env('TWO_FACTOR_CODE_LENGTH') ?: 6),
    ],

    // ⭐ Wired up (test-writing session 11): PAYMENT_EXPIRY_MINUTES was another .env.example-only
    // placeholder — SecurePaymentService hardcoded a 15-minute checkout window. Same default (15)
    // preserved.
    'secure_payment' => [
        'expiry_minutes' => (int) (env('PAYMENT_EXPIRY_MINUTES') ?: 15),
    ],

    // Telegram bots/Yes for events that the admin has enabled from the "Notification Settings" page of their "bot" channel
    //. Both Bot APIs are compatible with the Telegram format (yes they implement the same endpoint with a different
    // domain), so a single TelegramChannel covers both. If the token/chatID
    // is not set, silent sending (with just a Log::info) is ignored — no request
    // is required.
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
        'api_base' => env('TELEGRAM_API_BASE') ?: 'https://api.telegram.org',
    ],

    'bale' => [
        'bot_token' => env('BALE_BOT_TOKEN'),
        'chat_id' => env('BALE_CHAT_ID'),
        'api_base' => env('BALE_API_BASE') ?: 'https://tapi.bale.ai',
    ],
];
