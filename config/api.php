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

    'rate_limits' => [
        // ورود/تأیید/ارسال دوباره: کلید «آی‌پی + شماره یا challenge» و سقف جدای خود آی‌پی
        // (سقف آی‌پی بالاتر است چون کاربران اپراتورهای موبایل پشت CGNAT آی‌پی مشترک دارند)
        'login_per_subject_per_minute' => (int) (env('API_LOGIN_PER_SUBJECT') ?: 5),
        'login_per_ip_per_minute' => (int) (env('API_LOGIN_PER_IP') ?: 30),
        'authenticated_per_minute' => (int) (env('API_RATE_PER_MINUTE') ?: 120),
    ],
];
