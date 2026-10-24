<?php

namespace App\Support\Security;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;

/**
 * سیاست CSP برنامه (بسته‌ی مجوز 5د، مرحله‌ی ۱: فقط گزارش — Content-Security-Policy-Report-Only).
 *
 * مرحله‌ای: اول با SECURITY_CSP=report گزارش‌ها جمع می‌شود (storage/logs/csp-*.log)، بعد از اطمینان enforce. اسکریپت‌ها و
 * استایل‌های درون‌خطی صفحه‌ها هنوز زیادند، پس 'unsafe-inline' می‌ماند؛ آنچه همین الان محدود می‌شود مبدأهاست: اسکریپت فقط از
 * خود سایت، اینماد و (در توسعه) سرور Vite؛ ارسال فرم فقط به خود سایت و درگاه‌های پرداخت؛ frame فقط خود سایت؛ object هیچ.
 *
 * نباید بشکند (تست: ContentSecurityPolicyTest): لوگوی اینماد (اسکریپت، تصویر و لینک trustseal.enamad.ir)، فرم POST خودکار
 * درگاه‌ها (payments/gateway-redirect)، Vite (build و hot)، PWA (sw.js، manifest)، دامنه‌ی مرکزی و ساب‌دامین سالن‌ها.
 */
class ContentSecurityPolicy
{
    public function header(): string
    {
        return config('security.csp.mode') === 'enforce' ? 'Content-Security-Policy' : 'Content-Security-Policy-Report-Only';
    }

    public function enabled(): bool
    {
        return in_array(config('security.csp.mode'), ['report', 'enforce'], true);
    }

    public function build(Request $request): string
    {
        $site = $this->siteOrigins($request);
        $enamad = (array) config('security.csp.enamad_hosts', []);
        $hot = $this->viteHotOrigins();

        $directives = [
            'default-src' => ["'self'", ...$site],
            'script-src' => ["'self'", "'unsafe-inline'", ...$site, ...$enamad, ...$hot['http']],
            'style-src' => ["'self'", "'unsafe-inline'", ...$site, ...$hot['http']],
            'img-src' => ["'self'", 'data:', 'blob:', ...$site, ...$enamad, ...(array) config('security.csp.image_hosts', [])],
            'font-src' => ["'self'", 'data:', ...$site, ...$hot['http']],
            'connect-src' => ["'self'", ...$site, ...$hot['http'], ...$hot['ws']],
            'media-src' => ["'self'", 'blob:', ...$site],
            'worker-src' => ["'self'", 'blob:'],
            'manifest-src' => ["'self'", ...$site],
            'frame-src' => ["'self'"],
            'frame-ancestors' => ["'self'"],
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'", ...$site, ...(array) config('security.csp.payment_hosts', [])],
        ];

        $parts = [];
        foreach ($directives as $name => $sources) {
            $parts[] = $name.' '.implode(' ', array_values(array_unique($sources)));
        }

        $parts[] = 'report-uri '.url('/csp-report');

        return implode('; ', $parts);
    }

    /**
     * مبدأهای خود پلتفرم: APP_URL (فایل‌های Vite و storage با همین آدرس ساخته می‌شوند، حتی روی ساب‌دامین سالن) و دامنه‌ی
     * مرکزی با همه‌ی ساب‌دامین‌هایش.
     *
     * @return list<string>
     */
    private function siteOrigins(Request $request): array
    {
        $origins = [];

        $appUrl = (string) config('app.url');
        if ($appUrl !== '' && ($host = parse_url($appUrl, PHP_URL_HOST))) {
            $port = parse_url($appUrl, PHP_URL_PORT);
            $origins[] = (parse_url($appUrl, PHP_URL_SCHEME) ?: 'https').'://'.$host.($port ? ':'.$port : '');
        }

        $central = (string) config('app.central_domain');
        if ($central !== '') {
            $scheme = $request->isSecure() ? 'https' : 'http';
            $port = in_array($request->getPort(), [80, 443], true) ? '' : ':'.$request->getPort();
            $origins[] = $scheme.'://'.$central.$port;
            $origins[] = $scheme.'://*.'.$central.$port;
        }

        return $origins;
    }

    /** @return array{http: list<string>, ws: list<string>} */
    private function viteHotOrigins(): array
    {
        if (! Vite::isRunningHot()) {
            return ['http' => [], 'ws' => []];
        }

        $hot = rtrim((string) @file_get_contents(Vite::hotFile()));
        $origin = preg_replace('#^(https?://[^/]+).*$#', '$1', $hot) ?: $hot;

        return ['http' => [$origin], 'ws' => [preg_replace('#^http#', 'ws', $origin)]];
    }
}
