<?php

return [

    'name' => env('APP_NAME', 'Laravel'),

    'env' => env('APP_ENV', 'production'),

    'debug' => (bool) env('APP_DEBUG', false),

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Central (tenant-less) domain — SaaS Phase 2, محور «۳. ساب‌دامین اختصاصی»
    |--------------------------------------------------------------------------
    |
    | ⭐ وقتی این مقدار خالیه (پیش‌فرض)، رفتار برنامه دقیقاً همون چیزیه که فاز ۱ و ۲ همیشه
    | بوده: مسیرهای مشتری فقط زیر پیشوند /s/{salon_slug} ثبت می‌شن (routes/web.php).
    | هیچ تستی این مقدار رو ست نمی‌کنه، پس این تغییر صفر ریسک برای ۱۰۱۳ تست موجوده.
    |
    | وقتی مقداردهی بشه (مثلاً 127.0.0.1.nip.io برای تست لوکال، یا دامنه‌ی واقعی در
    | production)، routes/web.php **علاوه بر** همون Route::prefix('s/{salon_slug}') قبلی
    | (که همیشه ثبت می‌مونه)، یک Route::domain('{salon_slug}.'.این‌مقدار) هم اضافه می‌کنه —
    | یعنی سالن هم از ساب‌دامین (rasta-demo.127.0.0.1.nip.io) هم از مسیر قدیمی /s/{slug}
    | هم‌زمان قابل‌دسترسیه (طبق تصمیم مستندشده در docs/WILDCARD_SUBDOMAIN_DEPLOYMENT.md —
    | «لینک قدیمی نباید بشکنه» — و تأیید صریح ابوالفضل، ۲۰۲۶-۰۹-۱۹). همون middleware فعلی
    | (ResolveSalonFromRoute) بدون تغییر برای هر دو حالت کار می‌کنه، چون Route::domain()
    | پارامتر salon_slug رو دقیقاً مثل یک بخش URI پر می‌کنه.
    |
    | ⚠️ این یک toggle سطح-boot ـه (نه تصمیم به‌ازای هر request) — عمداً، چون این پروژه از
    | `php artisan route:cache` در production استفاده می‌کنه (docker/entrypoint.sh) و
    | route:cache فایل routes/web.php رو فقط یک‌بار (زمان build کش) اجرا می‌کنه، نه به‌ازای
    | هر بازدیدکننده؛ یک شاخه‌بندی مبتنی بر Host هر request در همون فایل، زیر route:cache
    | خراب می‌شد چون فقط همون یک اجرای اول منجمد می‌موند.
    |
    | ⚠️ ریسک شناخته‌شده و صریحاً پذیرفته‌شده (تصمیم تأییدشده با ابوالفضل، ۲۰۲۶-۰۹-۱۹): چون
    | SESSION_DOMAIN ایزوله است (تصمیم قبلی، هنوز پابرجا)، کاربری که از لینک قدیمی
    | /s/{slug} وارد شده و بعد روی یک لینک داخلی (که حالا به ساب‌دامین اشاره می‌کنه — به
    | routes/web.php نگاه کن، «آخرین route ثبت‌شده برای هر نام برنده می‌شه») کلیک می‌کنه،
    | عملاً به یک هاست دیگه navigate می‌شه و ممکنه logout به نظر برسه. جزئیات کامل در
    | routes/web.php، کنار همون Route::domain(...).
    |
    */

    'central_domain' => env('CENTRAL_DOMAIN'),

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    'locale' => env('APP_LOCALE', 'fa'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    'salon_address' => env('SALON_ADDRESS', 'آدرس سالن زیبایی'),

    'aliases' => [
        'Route' => Illuminate\Support\Facades\Route::class,
        'Excel' => Maatwebsite\Excel\Facades\Excel::class,
        'PDF' => Barryvdh\DomPDF\Facade\Pdf::class,
        'Verta' => \Hekmatinasser\Verta\Verta::class,
        'Kavenegar' => Kavenegar\Laravel\Facade::class,
    ],
];
