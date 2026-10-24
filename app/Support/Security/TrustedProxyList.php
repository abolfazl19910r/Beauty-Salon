<?php

namespace App\Support\Security;

/**
 * فهرست پروکسی‌های مورد اعتماد از config/trustedproxy.php (TRUSTED_PROXIES) — یک منبع برای Laravel (TrustProxies) و nginx
 * کانتینر (security:nginx-real-ip). با برداشتن یا عوض کردن CDN فقط همین یک مقدار عوض می‌شود.
 */
class TrustedProxyList
{
    /** @return list<string>|string|null  فهرست IP/CIDR، «*» برای همه، یا null برای هیچ */
    public static function resolve(): array|string|null
    {
        $entries = array_filter(array_map('trim', explode(',', (string) config('trustedproxy.proxies', 'none'))));

        if ($entries === [] || in_array('none', $entries, true)) {
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
