<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;

/**
 * ⭐ پروکسی‌های مورد اعتماد از config/trustedproxy.php، هنگام درخواست خونده می‌شن.
 * (TrustProxies::at() در bootstrap/app.php قبل از بارگذاری config/env اجرا می‌شه، پس اونجا قابل تنظیم نیست.)
 */
class TrustProxies extends Middleware
{
    protected function proxies()
    {
        $entries = array_filter(array_map('trim', explode(',', (string) config('trustedproxy.proxies', 'cloudflare'))));

        if (in_array('none', $entries, true)) {
            return null;
        }

        if (in_array('*', $entries, true)) {
            return '*';
        }

        $proxies = [];
        foreach ($entries as $entry) {
            array_push($proxies, ...($entry === 'cloudflare' ? (array) config('trustedproxy.cloudflare', []) : [$entry]));
        }

        return array_values(array_unique($proxies)) ?: null;
    }
}
