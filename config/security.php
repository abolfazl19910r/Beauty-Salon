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

    /*
    | CSP مرحله‌ای (App\Support\Security\ContentSecurityPolicy): report = فقط گزارش، enforce = اجرا (پیش‌فرض)، off = خاموش.
    | گزارش‌ها: POST /csp-report ← storage/logs/csp-*.log (۱۴ روز).
    */
    'csp' => [
        // ⭐ ۲۰۲۶-۱۰-۳۱ (تصمیم ابوالفضل): پیش‌فرض enforce — در Report-Only آزمون‌های دستی لوکال هیچ گزارشی ثبت نشد
        'mode' => in_array(env('SECURITY_CSP'), ['off', 'report', 'enforce'], true) ? env('SECURITY_CSP') : 'enforce',

        // لوگوی اینماد: کد پنل اینماد اسکریپت یا تصویر از همین میزبان است و لینکش به trustseal.enamad.ir می‌رود
        'enamad_hosts' => ['https://trustseal.enamad.ir', 'https://*.enamad.ir'],

        // تصویرهای بیرونی که صفحه‌ها هنوز دارند (قالب پیش‌فرض صفحه‌ی سالن)
        'image_hosts' => ['https://images.unsplash.com'],

        // فرم POST خودکار درگاه‌ها (resources/views/payments/gateway-redirect.blade.php) و بازگشت‌های بانک
        'payment_hosts' => [
            'https://*.shaparak.ir',
            'https://*.zarinpal.com', 'https://zarinpal.com',
            'https://*.zibal.ir', 'https://zibal.ir',
            'https://*.vandar.io',
            'https://*.asanpardakht.ir',
            'https://*.sep.ir',
            'https://*.behpardakht.com',
            'https://*.pec.ir',
        ],
    ],

];
