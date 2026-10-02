<?php

/*
| بسته‌ی ۰ اپلیکیشن — آزمایش پوش (فقط برنچ experiment/push-probe، ۲۰۲۶-۱۰-۰۲).
| دستور: php artisan push:probe — راهنما: experiments/push-probe/README.md
| این تنظیمات به هیچ بخش دیگری از برنامه وصل نیست.
*/

return [
    'fcm' => [
        // مسیر فایل JSON حساب سرویس فایربیس (Service account) — بیرون از public_html، هرگز commit نشود
        'credentials' => env('PUSH_PROBE_FCM_CREDENTIALS') ?: null,
        'oauth_url' => 'https://oauth2.googleapis.com/token',
        'api_url' => 'https://fcm.googleapis.com',
        // راه دوم وقتی سرور ایران به گوگل نمی‌رسد: https://<دامنه‌ی Worker>/<PROXY_SECRET>
        'proxy' => env('PUSH_PROBE_FCM_PROXY') ?: null,
        // همان کانالی که اپ آزمایشی می‌سازد
        'channel_id' => 'probe',
        // پیام تا این مدت (ثانیه) برای گوشی خاموش/بی‌اینترنت نگه داشته می‌شود
        'ttl' => (int) (env('PUSH_PROBE_FCM_TTL') ?: 3600),
    ],

    'ntfy' => [
        // سرور ntfy خودمیزبان (راه بدون گوگل). آزمایش لوکال: http://127.0.0.1:8090 — گوشی همان را با IP شبکه‌ی محلی می‌بیند
        'server' => env('PUSH_PROBE_NTFY_SERVER') ?: null,
        // فقط اگر روی سرور ntfy کاربر/توکن تعریف کرده‌اید
        'token' => env('PUSH_PROBE_NTFY_TOKEN') ?: null,
    ],

    'timeout' => (int) (env('PUSH_PROBE_TIMEOUT') ?: 20),

    // هر ارسال یک خط اینجا ثبت می‌شود (برای پر کردن فرم نتیجه)
    'log' => env('PUSH_PROBE_LOG') ?: storage_path('logs/push-probe.csv'),
];
