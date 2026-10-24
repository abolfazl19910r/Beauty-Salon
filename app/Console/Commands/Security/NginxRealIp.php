<?php

namespace App\Console\Commands\Security;

use App\Support\Security\TrustedProxyList;
use Illuminate\Console\Command;

/**
 * پیکربندی real_ip برای nginx کانتینر از همان TRUSTED_PROXIES برنامه (بسته‌ی مجوز 5د). entrypoint هر بار پیش از بالا آمدن
 * nginx خروجی را در /etc/nginx/conf.d/00-real-ip.conf می‌نویسد (docker/nginx-setup.sh).
 *
 * - پشت CDN: $remote_addr در nginx (لاگ دسترسی، limit_conn) IP واقعی مشتری می‌شود، فقط وقتی درخواست از یکی از همین
 *   رنج‌ها آمده؛ آدرس سمت راست X-Forwarded-For که خود لبه گذاشته ملاک است (real_ip_recursive)، پس سرآیند جعلی بی‌اثر است.
 * - بدون CDN (خالی/none): هیچ set_real_ip_from — IP مستقیم ثبت می‌شود.
 * - $mahru_trusted_proxy (geo روی آدرس واقعی اتصال): ریدایرکت HTTPS مبدا به سرآیند X-Forwarded-Proto لبه‌ی مورد اعتماد اعتماد کند.
 *
 * PHP آدرس اصلی اتصال را می‌گیرد (fastcgi REMOTE_ADDR = $realip_remote_addr) تا TrustProxies خود Laravel با همان فهرست
 * تصمیم بگیرد و X-Forwarded-Proto لبه همچنان دیده شود.
 */
class NginxRealIp extends Command
{
    protected $signature = 'security:nginx-real-ip';

    protected $description = 'Print the nginx real_ip configuration for TRUSTED_PROXIES';

    public function handle(): int
    {
        $proxies = TrustedProxyList::resolve();
        $ranges = $proxies === '*' ? ['0.0.0.0/0', '::/0'] : array_values(array_filter((array) $proxies, [$this, 'valid']));

        $lines = ['# ساخته‌شده با php artisan security:nginx-real-ip از TRUSTED_PROXIES — دستی ویرایش نکنید'];
        if ($ranges === []) {
            $lines[] = '# بدون پروکسی مورد اعتماد (حالت بدون CDN): IP مستقیم همان IP واقعی است';
        }
        foreach ($ranges as $range) {
            $lines[] = "set_real_ip_from {$range};";
        }
        if ($ranges !== []) {
            $lines[] = 'real_ip_header X-Forwarded-For;';
            $lines[] = 'real_ip_recursive on;';
        }
        $lines[] = 'geo $realip_remote_addr $mahru_trusted_proxy {';
        $lines[] = '    default 0;';
        foreach ($ranges as $range) {
            $lines[] = "    {$range} 1;";
        }
        $lines[] = '}';

        $this->line(implode("\n", $lines));

        return self::SUCCESS;
    }

    /** فقط IP یا CIDR معتبر وارد nginx شود (وگرنه nginx بالا نمی‌آید) */
    public function valid(string $entry): bool
    {
        [$ip, $mask] = array_pad(explode('/', $entry, 2), 2, null);
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return false;
        }
        $max = str_contains($ip, ':') ? 128 : 32;

        return $mask === null || (ctype_digit($mask) && (int) $mask <= $max);
    }
}
