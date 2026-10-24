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
        return \App\Support\Security\TrustedProxyList::resolve();
    }
}
