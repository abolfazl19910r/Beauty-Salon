<?php

use Illuminate\Support\Str;

return [

    'driver' => env('SESSION_DRIVER', 'database'),

    'lifetime' => env('SESSION_LIFETIME', 120),

    'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),

    'encrypt' => env('SESSION_ENCRYPT', false),

    'files' => storage_path('framework/sessions'),

    'connection' => env('SESSION_CONNECTION'),

    'table' => env('SESSION_TABLE', 'sessions'),

    'store' => env('SESSION_STORE'),

    'lottery' => [2, 100],

    // ⭐ بررسی امنیتی M5 (۲۰۲۶-۱۱-۰۵): روی HTTPS (SESSION_SECURE_COOKIE؛ کوکی همیشه فقط مال همان میزبان، 'domain' پایین)، نام
    // کوکی پیشوند «__Host-» می‌گیرد: مرورگر چنین کوکی‌ای را فقط از خود همان میزبان و بی Domain می‌پذیرد، پس هیچ زیردامنه‌ای (حتی با
    // اسکریپت تزریقی) نمی‌تواند session دیگری را جا بیندازد. روی HTTP لوکال (secure=false) پیشوند ممکن نیست و نام همان قبلی است.
    'cookie' => (static function (): string {
        $name = env('SESSION_COOKIE') ?: Str::slug(env('APP_NAME', 'laravel'), '_').'_session';
        $hostOnlySecure = env('SESSION_SECURE_COOKIE') === true && (env('SESSION_PATH') ?: '/') === '/';

        return $hostOnlySecure && ! str_starts_with($name, '__Host-') ? '__Host-'.$name : $name;
    })(),

    'path' => env('SESSION_PATH', '/'),

    // M5: همیشه null — کوکی فقط مال همان میزبان. SESSION_DOMAIN (مثل «.arisheh.ir» قدیمی) عمداً خوانده نمی‌شود تا یک خط جامانده در
    // .env کوکی را دوباره بین همه‌ی سالن‌ها مشترک نکند.
    'domain' => null,

    'secure' => env('SESSION_SECURE_COOKIE'),

    'http_only' => env('SESSION_HTTP_ONLY', true),

    'same_site' => env('SESSION_SAME_SITE', 'lax'),

    'partitioned' => env('SESSION_PARTITIONED_COOKIE', false),

];
