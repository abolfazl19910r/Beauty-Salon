<?php

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * php artisan security:nginx-real-ip — nginx کانتینر real_ip را از همان TRUSTED_PROXIES برنامه می‌سازد (بسته‌ی مجوز 5د).
 * آزموده با nginx و php-fpm واقعی: پشت پروکسی مورد اعتماد لاگ دسترسی آدرس سمت راست X-Forwarded-For را دارد (نه آدرس جعلی
 * سمت چپ)، بدون پروکسی IP مستقیم؛ PHP همیشه آدرس اصلی اتصال را می‌گیرد.
 */
class NginxRealIpCommandTest extends TestCase
{
    private function render(string $proxies): string
    {
        config(['trustedproxy.proxies' => $proxies]);
        Artisan::call('security:nginx-real-ip');

        return Artisan::output();
    }

    public function test_without_a_cdn_nothing_is_trusted_and_the_direct_ip_is_logged(): void
    {
        foreach (['', 'none'] as $value) {
            $out = $this->render($value);
            $this->assertStringNotContainsString('set_real_ip_from', $out);
            $this->assertStringNotContainsString('real_ip_header', $out);
            $this->assertMatchesRegularExpression('/geo \$realip_remote_addr \$mahru_trusted_proxy \{\s*default 0;\s*\}/', $out);
        }
    }

    public function test_behind_a_cdn_its_ranges_are_trusted_with_the_rightmost_forwarded_address(): void
    {
        $out = $this->render('185.143.232.0/22,2a0f:3c00::/32');

        $this->assertStringContainsString("set_real_ip_from 185.143.232.0/22;\nset_real_ip_from 2a0f:3c00::/32;", $out);
        $this->assertStringContainsString('real_ip_header X-Forwarded-For;', $out);
        $this->assertStringContainsString('real_ip_recursive on;', $out);
        $this->assertStringContainsString('    185.143.232.0/22 1;', $out);
    }

    public function test_the_cloudflare_keyword_and_wildcard_expand(): void
    {
        $this->assertSame(22, substr_count($this->render('cloudflare'), 'set_real_ip_from'));

        $all = $this->render('*');
        $this->assertStringContainsString('set_real_ip_from 0.0.0.0/0;', $all);
        $this->assertStringContainsString('set_real_ip_from ::/0;', $all);
    }

    public function test_junk_never_reaches_nginx(): void
    {
        $out = $this->render('10.0.0.0/8, <html>, 1.2.3.4/99, ;rm -rf, 999.1.1.1, 192.168.1.1');

        $this->assertSame(2, substr_count($out, 'set_real_ip_from'));
        $this->assertStringContainsString('set_real_ip_from 10.0.0.0/8;', $out);
        $this->assertStringContainsString('set_real_ip_from 192.168.1.1;', $out);
        $this->assertStringNotContainsString('html', $out);
    }
}
