<?php

/*
|--------------------------------------------------------------------------
| امنیت مستقل از CDN (بسته‌ی مجوز 5د، ۲۰۲۶-۱۰-۲۵)
|--------------------------------------------------------------------------
| هدرها در خود برنامه فرستاده می‌شوند (App\Http\Middleware\SecurityHeaders)، نه در لبه‌ی CDN و نه فقط در nginx کانتینر؛
| با برداشتن یا عوض کردن CDN، یا اجرا روی XAMPP/DirectAdmin، همین‌ها برقرار می‌مانند. nginx فقط برای فایل‌های ایستا
| (/build، /storage) همین هدرها را می‌گذارد (docker/nginx/security-headers.conf).
*/

return [

    'headers' => [
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-Content-Type-Options' => 'nosniff',
        // مبدا (نه آدرس کامل) به سایت‌های دیگر می‌رود؛ استعلام لوگوی اینماد دامنه را از همین می‌شناسد
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        // هیچ صفحه‌ای دوربین/میکروفون/مکان نمی‌خواهد؛ عکس دوره با input فایل گرفته می‌شود که به این مجوز نیاز ندارد
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()',
    ],

    // فقط روی HTTPS (مستقیم یا از پشت پروکسی مورد اعتماد). 0 = خاموش. preload عمداً نیست (برگشت‌ناپذیر است).
    'hsts' => [
        'max_age' => is_numeric(env('HSTS_MAX_AGE')) ? (int) env('HSTS_MAX_AGE') : 31536000,
        // ساب‌دامین‌های سالن‌ها هم فقط HTTPS (گواهی wildcard)؛ مقدار خالی در .env = پیش‌فرض
        'include_subdomains' => ! in_array(env('HSTS_INCLUDE_SUBDOMAINS'), [false, 'false', '0', 0], true),
    ],

];
