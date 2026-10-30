<?php

return [

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

    // دامنه‌ی لینک کوتاه پیامک‌ها (ShortLinkService)؛ خالی = CENTRAL_DOMAIN یا میزبان APP_URL. دامنه‌ی کوتاه‌تر = پیامک ارزان‌تر.
    'sms_links' => [
        'host' => env('SMS_LINK_HOST'),
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
        // ⭐ ۲۰۲۶-۱۰-۲۴: true = هیچ پیامکی به کاوه‌نگار نمی‌رود، در هیچ محیطی (سرور واقعی پیش از آماده شدن خط/قالب‌ها)؛ متن و کد در لاگ
        'log_only' => filter_var(env('SMS_LOG_ONLY', false), FILTER_VALIDATE_BOOL),

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
        // ⭐ ۲۰۲۶-۰۹-۲۵: آدرس‌های production درگاه پرداخت طبق مستندات رسمی فعلی زرین‌پال.
        'payment_api_url' => env('ZARINPAL_PAYMENT_API_URL', 'https://payment.zarinpal.com/pg/v4/payment'),
        'start_pay_url' => env('ZARINPAL_START_PAY_URL', 'https://payment.zarinpal.com/pg/StartPay'),

        // ⭐ بسته‌ی 5ز (۲۰۲۶-۱۰-۳۰): بخش payout (ZARINPAL_PAYOUT_*) حذف شد — تسویه‌ی خودکار زرین‌پال خاموش است.

        // ⭐ بسته‌ی 5ز: «اتصال حساب زرین‌پال» (OAuth 2.0 + PKCE، /apiDocs/connect.html). client_id و redirect_uri ثبت‌شده را پشتیبانی زرین‌پال
        // می‌دهد؛ تا client_id خالی است دکمه‌ی اتصال پنهان و مسیرهایش ۴۰۴ است (ورود دستی مرچنت سر جایش). redirect_uri خالی = مسیر
        // admin.payment-gateways.zarinpal.callback روی APP_URL. mcc_id = دسته‌بندی کسب‌وکار برای TerminalAdd (فهرستش در مستندات نیست).
        'connect' => [
            'client_id' => env('ZARINPAL_CONNECT_CLIENT_ID'),
            'redirect_uri' => env('ZARINPAL_CONNECT_REDIRECT_URI'),
            'mcc_id' => env('ZARINPAL_CONNECT_MCC_ID'),
            'scopes' => 'terminal.read terminal bank_account.read',
            'oauth_url' => env('ZARINPAL_CONNECT_OAUTH_URL') ?: 'https://next.zarinpal.com/api/oauth',
            'graphql_url' => env('ZARINPAL_CONNECT_GRAPHQL_URL') ?: 'https://next.zarinpal.com/api/v4/graphql',
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

    // ربات پلتفرم (۲۰۲۶-۱۰-۰۱): یک ربات بله و یک ربات تلگرام برای کل پلتفرم؛ هر کاربر با «/start <کد>» وصل می‌شود و
    // اعلان‌ها فقط به گفت‌وگوی خودش می‌رود (BotLink). هر دو Bot API یک قالب‌اند، فقط دامنه فرق دارد. توکن خالی = آن
    // پیام‌رسان خاموش. ⚠️ از سرور داخل ایران api.telegram.org در دسترس نیست — تلگرام فقط با سرور بیرون یا پراکسی
    // (TELEGRAM_API_BASE). webhook: php artisan bot:webhook bale (و telegram).
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'username' => env('TELEGRAM_BOT_USERNAME'),
        'api_base' => env('TELEGRAM_API_BASE') ?: 'https://api.telegram.org',
    ],

    'bale' => [
        'bot_token' => env('BALE_BOT_TOKEN'),
        'username' => env('BALE_BOT_USERNAME'),
        'api_base' => env('BALE_API_BASE') ?: 'https://tapi.bale.ai',
    ],

    'bot' => [
        // ⭐ بسته‌ی ۸ (۲۰۲۶-۱۰-۲۲): ربات کنار گذاشته شد ولی کدش ماند. خاموش (پیش‌فرض) = هیچ‌جا دیده نمی‌شود و پیامی نمی‌رود؛
        // true = همه‌چیز مثل قبل (کارت اتصال، ستون «ربات» تنظیمات اعلان، بخش ربات تنظیمات پلتفرم، webhook و bot:poll).
        'enabled' => filter_var(env('BOT_ENABLED') ?: false, FILTER_VALIDATE_BOOLEAN),
        // بخش مخفی آدرس webhook (هر دو پیام‌رسان)؛ خالی = webhook غیرفعال
        'webhook_secret' => env('BOT_WEBHOOK_SECRET'),
    ],
];
