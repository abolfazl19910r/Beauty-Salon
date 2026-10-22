<?php

namespace App\Support;

/**
 * آدرس مطلق روی دامنه‌ی اصلی ماهرو (بسته‌ی ۵، ۲۰۲۶-۱۰-۲۰).
 *
 * route() برای مسیرهای بی‌دامنه (صفحه‌ی اصلی، /app، /my-salons) میزبان درخواست جاری را می‌گذارد؛ روی ساب‌دامین سالن
 * («rasta.arisheh.ir/app») این همان صفحه‌ی خود سالن می‌شود، نه صفحه‌ی ماهرو. اینجا وقتی CENTRAL_DOMAIN پر است میزبان صریحاً
 * دامنه‌ی اصلی است (با scheme و پورت APP_URL — محلی: http://192.168.1.2.nip.io:8000)؛ بدون آن همان url() (یک میزبان برای همه).
 */
final class CentralUrl
{
    public static function to(string $path = '/'): string
    {
        $path = '/'.ltrim($path, '/');
        $central = config('app.central_domain');

        if (! $central) {
            return url($path);
        }

        $appUrl = (string) config('app.url');
        $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?: 'https';
        $port = parse_url($appUrl, PHP_URL_PORT);

        return $scheme.'://'.$central.($port ? ':'.$port : '').($path === '/' ? '' : $path);
    }

    /** route() یک مسیر بی‌دامنه، روی دامنه‌ی اصلی */
    public static function route(string $name, array $parameters = []): string
    {
        return self::to(route($name, $parameters, false));
    }
}
