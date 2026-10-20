<?php

/*
 * API نسخه‌ی ۱ برای اپ‌های موبایل (بسته‌ی ۱ اپلیکیشن، ۲۰۲۶-۱۰-۱۰).
 * الگوی env('X') ?: پیش‌فرض عمداً: کلید خالی در .env نباید عدد را صفر کند.
 */
return [

    // توکن اپ بعد از این تعداد روز بی‌استفاده باطل می‌شود؛ هر استفاده دوباره تمدیدش می‌کند (تصمیم ۲۰۲۶-۱۰-۱۰)
    'token_idle_days' => (int) (env('API_TOKEN_IDLE_DAYS') ?: 90),

    'login' => [
        // عمر challenge مرحله‌ی دوم ورود (کد خودش با auth.verification_code_expire_minutes منقضی می‌شود)
        'challenge_ttl_minutes' => (int) (env('API_LOGIN_CHALLENGE_TTL_MINUTES') ?: 10),
        // بعد از این تعداد کد غلط، challenge می‌سوزد و ورود باید از رمز شروع شود
        'max_code_attempts' => (int) (env('API_LOGIN_MAX_CODE_ATTEMPTS') ?: 5),
        'resend_cooldown_seconds' => (int) (env('API_LOGIN_RESEND_COOLDOWN') ?: 60),
        'max_resends' => (int) (env('API_LOGIN_MAX_RESENDS') ?: 3),
    ],

    // اپ «ماهرو» (مشتری، بسته‌ی ۳): صفحه‌ی برگشت از درگاه، مشتری را با این scheme به اپ برمی‌گرداند (mahru://payment?...)
    'customer_app_scheme' => env('CUSTOMER_APP_SCHEME') ?: 'mahru',

    // دکمه‌ی «باز کردن در اپ ماهرو» صفحه‌ی وب سالن روی اندروید (بسته‌ی ۳ب، App\Support\CustomerAppLink): شناسه‌ی اپ برای لینک intent.
    'customer_app_package' => env('CUSTOMER_APP_PACKAGE') ?: 'ir.mahru',
    // بسته‌ی ۵: نصب از صفحه‌ی «دانلود اپ ماهرو» (/app با APK آپلودشده‌ی ابرمدیر). این کلید فقط جایگزین دستی آن صفحه است (لینک بیرونی).
    'customer_app_download_url' => env('CUSTOMER_APP_DOWNLOAD_URL') ?: null,
    'staff_app_package' => env('STAFF_APP_PACKAGE') ?: 'ir.mahru.staff',

    // Android App Links (بسته‌ی ۵): اثرانگشت SHA-256 گواهی امضای اپ مشتری (expo.dev ← Credentials ← Android ← ir.mahru)، چند تا با ویرگول.
    // عمومی است، نه راز؛ در /.well-known/assetlinks.json منتشر می‌شود. خالی = فهرست خالی (لینک سالن همان وب را باز می‌کند).
    'customer_app_sha256_fingerprints' => array_values(array_filter(array_map('trim', explode(',', (string) env('CUSTOMER_APP_SHA256_FINGERPRINTS', ''))))),

    // سقف حجم APK آپلودی ابرمدیر (مگابایت). upload_max_filesize و post_max_size خود PHP هم باید دست‌کم همین باشد.
    'app_release_max_mb' => (int) (env('APP_RELEASE_MAX_MB') ?: 150),

    // عمر لینک امضاشده‌ی «رفتن به درگاه» که API پرداخت به اپ می‌دهد (اپ آن را در مرورگر بیرونی باز می‌کند)
    'checkout_ttl_minutes' => (int) (env('API_CHECKOUT_TTL_MINUTES') ?: 15),

    'rate_limits' => [
        // ورود/تأیید/ارسال دوباره: کلید «آی‌پی + شماره یا challenge» و سقف جدای خود آی‌پی
        // (سقف آی‌پی بالاتر است چون کاربران اپراتورهای موبایل پشت CGNAT آی‌پی مشترک دارند)
        'login_per_subject_per_minute' => (int) (env('API_LOGIN_PER_SUBJECT') ?: 5),
        'login_per_ip_per_minute' => (int) (env('API_LOGIN_PER_IP') ?: 30),
        'authenticated_per_minute' => (int) (env('API_RATE_PER_MINUTE') ?: 120),
    ],
];
