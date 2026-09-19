<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

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
    | production)، routes/web.php به‌جاش یک گروه Route::domain('{salon_slug}.'.این‌مقدار)
    | ثبت می‌کنه — یعنی سالن از ساب‌دامین (rasta-demo.127.0.0.1.nip.io) تشخیص داده می‌شه،
    | نه از بخش URI. همون middleware فعلی (ResolveSalonFromRoute) بدون تغییر کار می‌کنه،
    | چون Route::domain() پارامتر salon_slug رو دقیقاً مثل یک بخش URI پر می‌کنه.
    |
    | ⚠️ این یک toggle سطح-boot ـه (نه تصمیم به‌ازای هر request) — عمداً، چون این پروژه از
    | `php artisan route:cache` در production استفاده می‌کنه (docker/entrypoint.sh) و
    | route:cache فایل routes/web.php رو فقط یک‌بار (زمان build کش) اجرا می‌کنه، نه به‌ازای
    | هر بازدیدکننده؛ یک شاخه‌بندی مبتنی بر Host هر request در همون فایل، زیر route:cache
    | خراب می‌شد چون فقط همون یک اجرای اول منجمد می‌موند.
    |
    | ⭐ محدودیت شناخته‌شده‌ی همین قدم اول: وقتی این مقدار ست باشه، مسیرهای /s/{slug} دیگه
    | ثبت نمی‌شن (fallback یعنی «اگه ست نشده باشه، همون رفتار فاز ۱»، نه «هر دو هم‌زمان
    | زنده باشن»). این‌که آیا لینک‌های قدیمی /s/{slug} باید هم‌زمان با ساب‌دامین فعال
    | بمونن (برای مهاجرت تدریجی) یک تصمیم بیزنسی بازه — به «قدم‌های باز» در
    | Rasta_unified_prompt.md نگاه کن.
    |
    */

    'central_domain' => env('CENTRAL_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'fa'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

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
