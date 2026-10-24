<?php

/*
|--------------------------------------------------------------------------
| نگهداری داده (بسته‌ی مجوز 5د، ۲۰۲۶-۱۰-۲۵) — مرجع: سند پروژه claude/Licensing_requirements.md
|--------------------------------------------------------------------------
| ماده‌ی ۶۶۸ آیین دادرسی کیفری (= ماده‌ی ۳۳ قانون جرایم رایانه‌ای)، «ارائه‌دهندگان خدمات میزبانی داخلی»: اطلاعات کاربران
| (از جمله IP) دست‌کم تا شش ماه پس از خاتمه‌ی اشتراک؛ محتوا و داده‌ی ترافیک تغییرات دست‌کم پانزده روز.
| پیش‌فرض محتاطانه: لاگ دسترسی و IP شش ماه (۱۹۰ روز = شش ماه تقویمی + حاشیه)، قابل تنظیم فقط رو به بالا: مقدار کمتر از
| حداقل قانونی به همان حداقل برگردانده می‌شود (App\Support\Retention).
|
| پاک‌سازی خودکار بعد از مهلت: php artisan retention:prune (هر شب، bootstrap/app.php).
*/

return [

    // حداقل قانونی برای هر چیزی که IP یا هویت دارد (شش ماه تقویمی حداکثر ۱۸۴ روز است)
    'minimum_days' => 184,

    // پیش‌فرض همه‌ی موارد زیر
    'days' => is_numeric(env('RETENTION_DAYS')) ? (int) env('RETENTION_DAYS') : 190,

    // جدول security_logs (ورود، کارهای کلیدی با IP و user agent)
    'security_logs_days' => is_numeric(env('RETENTION_SECURITY_LOGS_DAYS')) ? (int) env('RETENTION_SECURITY_LOGS_DAYS') : null,

    // لاگ دسترسی nginx (storage/logs/nginx/access-YYYY-MM-DD.log): فایل‌های روزهای قبل فشرده (.gz)، بعد از مهلت پاک
    'access_log_days' => is_numeric(env('RETENTION_ACCESS_LOG_DAYS')) ? (int) env('RETENTION_ACCESS_LOG_DAYS') : null,
    'access_log_path' => env('RETENTION_ACCESS_LOG_PATH') ?: storage_path('logs/nginx'),

    // فایل storage/logs/security-*.log: config/logging.php همین حداقل و پیش‌فرض را با SECURITY_LOG_DAYS می‌خواند
    // (Laravel خودش فایل‌های daily قدیمی‌تر را پاک می‌کند).

];
