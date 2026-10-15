<?php

/*
 * پوش اپ‌ها (بسته‌ی ۴، ۲۰۲۶-۱۰-۱۵). الگوی env('X') ?: پیش‌فرض عمداً: کلید خالی در .env نباید عدد را صفر کند.
 *
 * حالت پلتفرم (off | shadow | live) را ابرمدیر از «تنظیمات پلتفرم» عوض می‌کند (App\Services\Push\PushMode)؛ PUSH_MODE فقط
 * پیش‌فرض پیش از اولین ذخیره است. shadow = دوره‌ی آزمایشی تصمیم ۲۰۲۶-۱۰-۰۲: پیامک مثل قبل + پوش، فقط برای سنجش نرخ رسید.
 */
return [

    'default_mode' => env('PUSH_MODE') ?: 'shadow',

    // مهلت «بدون رسید → پیامک» (تصمیم ۲، گزینه‌ی «ج»، ۲۰۲۶-۱۰-۱۰) به ثانیه
    'fallback_seconds' => [
        'urgent' => (int) (env('PUSH_FALLBACK_URGENT_SECONDS') ?: 60),
        'normal' => (int) (env('PUSH_FALLBACK_NORMAL_SECONDS') ?: 600),
    ],

    // دستگاهی که این تعداد پوش آخرش بی‌رسید مانده «ناسالم» است: پیام فوری مستقیم پیامک می‌شود تا اولین رسید تازه
    'unhealthy_after_unacked' => (int) (env('PUSH_UNHEALTHY_AFTER') ?: 2),

    // عمر پیام در FCM/ntfy اگر گوشی خاموش یا بی‌اینترنت است (ثانیه)
    'ttl_seconds' => [
        'urgent' => 3600,
        'normal' => 43200,
        'info' => 86400,
    ],

    // ردیف‌های push_messages قدیمی‌تر از این (روز) با push:prune پاک می‌شوند
    'retention_days' => (int) (env('PUSH_RETENTION_DAYS') ?: 90),

    'fcm' => [
        // مسیر فایل حساب سرویس فایربیس (نسبی = از ریشه‌ی پروژه). خالی = FCM خاموش؛ هیچ پوشی فرستاده نمی‌شود.
        'credentials' => env('PUSH_FCM_CREDENTIALS') ?: null,
        // data: پیام بی‌اعلان؛ اپ اعلان را خودش می‌سازد و رسید می‌فرستد (حتی وقتی بسته است).
        // notification: اعلان را اندروید می‌سازد؛ اگر روی گوشی‌ای task پس‌زمینه اجرا نشد، با این برگردانید (رسید فقط با باز کردن اپ).
        'style' => env('PUSH_FCM_STYLE') ?: 'data',
        'oauth_url' => 'https://oauth2.googleapis.com/token',
        'api_url' => 'https://fcm.googleapis.com',
        // Worker کلودفلر (کیت بسته‌ی ۰، experiments/push-probe/fcm-proxy) اگر سرور به گوگل نرسد؛ خالی = مستقیم
        'proxy' => env('PUSH_FCM_PROXY') ?: null,
        'timeout' => (int) (env('PUSH_FCM_TIMEOUT') ?: 10),
    ],

    // ntfy خودمیزبان برای دوره‌های قطعی اینترنت بین‌الملل (تصمیم ۲۰۲۶-۱۰-۰۲). سمت سرور آماده است؛ اتصال در اپ بعد از سرور مجازی داخل ایران.
    'ntfy' => [
        'server' => env('PUSH_NTFY_SERVER') ?: null,
        'token' => env('PUSH_NTFY_TOKEN') ?: null,
        'timeout' => (int) (env('PUSH_NTFY_TIMEOUT') ?: 10),
    ],
];
