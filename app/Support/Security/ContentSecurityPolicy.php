<?php

namespace App\Support\Security;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;

/**
 * سیاست CSP برنامه (بسته‌ی مجوز 5د؛ مرحله‌ی ۱ فقط گزارش، از ۲۰۲۶-۱۰-۳۱ پیش‌فرض اجرا — Content-Security-Policy).
 *
 * مرحله‌ای: اول با SECURITY_CSP=report گزارش‌ها جمع می‌شود (storage/logs/csp-*.log)، بعد از اطمینان enforce. اسکریپت‌ها و
 * استایل‌های درون‌خطی صفحه‌ها هنوز زیادند، پس 'unsafe-inline' می‌ماند؛ آنچه همین الان محدود می‌شود مبدأهاست: اسکریپت فقط از
 * خود سایت، اینماد و (در توسعه) سرور Vite؛ ارسال فرم فقط به خود سایت و درگاه‌های پرداخت؛ frame فقط خود سایت؛ object هیچ.
 *
 * ⭐ بررسی امنیتی ۲۰۲۶-۱۱-۰۴ (H3، مرحله‌ی ۱): «'unsafe-inline'» در script-src یعنی اسکریپت درون‌خطی تزریقی (مثل H1/H2) اجرا می‌شود.
 * هر درخواست یک nonce دارد (Vite::useCspNonce؛ تگ‌های Vite و همه‌ی <script>های درون‌خطی با @nonce). مرحله‌ی ۲ (۲۰۲۶-۱۱-۰۵): همه‌ی
 * handlerهای درون‌خطی (onclick/onmouseover=…) به data-ui-* و partials/ui-handlers رفتند، پس سیاست اجرایی script-src فقط nonce دارد.
 * security.csp.strict_scripts (SECURITY_CSP_STRICT_SCRIPTS): enforce (پیش‌فرض) | report (سیاست اجرایی قبلی با 'unsafe-inline' + یک
 * Report-Only سخت‌گیر؛ راه برگشت موقت) | off. nonce هرگز کنار 'unsafe-inline' در یک سیاست نمی‌آید (آن را بی‌اثر می‌کرد).
 * view تازه: هیچ on…= یا javascript: (تست InlineEventHandlersTest)؛ <script> درون‌خطی فقط با @nonce.
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

    /** off | report | enforce — script-src بی 'unsafe-inline' (H3) */
    public function strictScriptsMode(): string
    {
        $mode = config('security.csp.strict_scripts');

        return in_array($mode, ['off', 'report', 'enforce'], true) ? $mode : 'report';
    }

    /** nonce همین درخواست (یک بار ساخته و به تگ‌های Vite هم داده می‌شود) */
    public function nonce(): string
    {
        return Vite::cspNonce() ?? Vite::useCspNonce();
    }

    /**
     * هدرهای CSP این پاسخ: نام ← مقدار. حالت‌ها (mode × strict_scripts):
     * - strict=enforce: سیاست اصلی (اجرایی یا گزارشی، طبق mode) با script-src سخت‌گیر؛
     * - strict=report و mode=enforce: سیاست اجرایی فعلی + Report-Only سخت‌گیر؛
     * - strict=report و mode=report: تنها هدر Report-Only، سخت‌گیر؛
     * - strict=off: مثل پیش از H3.
     *
     * @return array<string, string>
     */
    public function headers(Request $request): array
    {
        $strict = $this->strictScriptsMode();

        if ($strict === 'enforce' || ($strict === 'report' && $this->header() === 'Content-Security-Policy-Report-Only')) {
            return [$this->header() => $this->build($request, strictScripts: true)];
        }

        $headers = [$this->header() => $this->build($request)];
        if ($strict === 'report') {
            $headers['Content-Security-Policy-Report-Only'] = $this->build($request, strictScripts: true);
        }

        return $headers;
    }

    public function enabled(): bool
    {
        return in_array(config('security.csp.mode'), ['report', 'enforce'], true);
    }

    /**
     * ⭐ ۲۰۲۶-۱۱-۰۴ (تصمیم ابوالفضل، راه «الف»): داشبورد Telescope (Vue با کامپایل قالب در مرورگر = `eval`، فونت `fonts.bunny.net`)
     * زیر CSP اجرایی سفید می‌ماند. فقط روی سیستم توسعه (`APP_ENV=local`) و فقط زیر مسیر Telescope سیاستی فرستاده نمی‌شود؛ بقیه‌ی صفحه‌های لوکال
     * مثل سرور سخت‌گیرند. روی سرور Telescope نصب نیست (`composer install --no-dev`) و این استثنا هیچ‌وقت فعال نمی‌شود.
     */
    public function appliesTo(Request $request): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        if (app()->environment('local') && config('telescope.domain') === null) {
            $path = trim((string) (config('telescope.path') ?: 'telescope'), '/');

            if ($path !== '' && $request->is($path, $path.'/*')) {
                return false;
            }
        }

        return true;
    }

    public function build(Request $request, bool $strictScripts = false): string
    {
        $site = $this->siteOrigins($request);
        $enamad = (array) config('security.csp.enamad_hosts', []);
        $hot = $this->viteHotOrigins();
        $inline = $strictScripts ? "'nonce-".$this->nonce()."'" : "'unsafe-inline'";

        $directives = [
            'default-src' => ["'self'", ...$site],
            'script-src' => ["'self'", $inline, ...$site, ...$enamad, ...$hot['http']],
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

        // نشانی IPv6 (Node 17+ روی ویندوز: «http://[::1]:5173») در دستور CSP قابل نوشتن نیست؛ فقط در توسعه (hot) کل طرح باز می‌شود.
        // vite.config.js سرور را روی 127.0.0.1 می‌گذارد تا این حالت پیش نیاید.
        if (str_contains($origin, '[')) {
            return ['http' => [str_starts_with($origin, 'https') ? 'https:' : 'http:'], 'ws' => [str_starts_with($origin, 'https') ? 'wss:' : 'ws:']];
        }

        return ['http' => [$origin], 'ws' => [preg_replace('#^http#', 'ws', $origin)]];
    }
}
