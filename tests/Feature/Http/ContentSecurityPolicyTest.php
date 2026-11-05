<?php

namespace Tests\Feature\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Vite;
use Tests\TestCase;

/**
 * CSP — بسته‌ی مجوز 5د (مرحله‌ی ۱ Report-Only؛ مرحله‌ی ۲ از ۲۰۲۶-۱۰-۳۱ پیش‌فرض enforce). چیزهایی که نباید بشکنند: اینماد، فرم خودکار درگاه‌ها، Vite، PWA و
 * ساب‌دامین سالن‌ها. App\Support\Security\ContentSecurityPolicy
 */
class ContentSecurityPolicyTest extends TestCase
{
    use RefreshDatabase;

    private function policy(string $uri = '/'): array
    {
        $header = (string) $this->get($uri)->assertOk()->headers->get('Content-Security-Policy');
        $this->assertNotSame('', $header, 'صفحه‌ی HTML سیاست اجرایی دارد');

        $directives = [];
        foreach (explode(';', $header) as $part) {
            $tokens = preg_split('/\s+/', trim($part));
            $directives[array_shift($tokens)] = $tokens;
        }

        return $directives;
    }

    /** ⭐ مرحله‌ی ۲ (تصمیم ابوالفضل ۲۰۲۶-۱۰-۳۱): پیش‌فرض enforce؛ در گزارش‌های Report-Only آزمون‌های دستی لوکال هیچ تخلفی نبود */
    public function test_pages_are_enforced_by_default_and_still_report(): void
    {
        config(['security.csp.strict_scripts' => 'off']);
        $response = $this->get('/');

        $response->assertHeaderMissing('Content-Security-Policy-Report-Only');
        $this->assertStringContainsString('report-uri '.url('/csp-report'), (string) $response->headers->get('Content-Security-Policy'));
    }

    public function test_report_mode_is_still_available(): void
    {
        config(['security.csp.mode' => 'report']);

        $this->get('/')->assertHeader('Content-Security-Policy-Report-Only')->assertHeaderMissing('Content-Security-Policy');
    }

    public function test_the_enamad_badge_script_image_and_link_are_allowed(): void
    {
        $csp = $this->policy();

        $this->assertContains('https://trustseal.enamad.ir', $csp['script-src']);
        $this->assertContains('https://trustseal.enamad.ir', $csp['img-src']);
        // لینک لوگو (a href با target=_blank) زیر CSP نیست؛ فقط frame و object بسته‌اند
        $this->assertSame(["'none'"], $csp['object-src']);
    }

    public function test_every_gateway_host_the_drivers_post_to_is_an_allowed_form_action(): void
    {
        $patterns = $this->policy()['form-action'];

        $hosts = [];
        foreach (glob(base_path('app/Payments/Drivers/*.php')) as $file) {
            preg_match_all('#https://([a-z0-9-]+(?:\.[a-z0-9-]+)+)#i', (string) file_get_contents($file), $m);
            array_push($hosts, ...array_map('strtolower', $m[1]));
        }
        $this->assertNotEmpty($hosts);

        foreach (array_unique($hosts) as $host) {
            $allowed = collect($patterns)->contains(function (string $source) use ($host) {
                $pattern = preg_replace('#^https://#', '', $source);

                return $pattern === $host || (str_starts_with($pattern, '*.') && str_ends_with($host, substr($pattern, 1)));
            });
            $this->assertTrue($allowed, "form-action درگاه {$host} را نمی‌پذیرد");
        }
    }

    public function test_pwa_and_inline_assets_keep_working(): void
    {
        $csp = $this->policy();

        $this->assertContains("'self'", $csp['worker-src']);
        $this->assertContains("'self'", $csp['manifest-src']);
        // H3 مرحله‌ی ۲: اسکریپت درون‌خطی فقط با nonce (نه 'unsafe-inline')؛ style درون‌خطی همچنان مجاز
        $this->assertNotEmpty(array_filter($csp['script-src'], fn ($t) => str_starts_with($t, "'nonce-")), 'اسکریپت‌های درون‌خطی با nonce');
        $this->assertContains("'unsafe-inline'", $csp['style-src']);
        $this->assertContains('data:', $csp['img-src']);
    }

    public function test_the_vite_dev_server_is_allowed_while_running_hot(): void
    {
        $hot = Vite::hotFile();
        file_put_contents($hot, 'http://localhost:5173');

        try {
            $csp = $this->policy();
        } finally {
            @unlink($hot);
        }

        $this->assertContains('http://localhost:5173', $csp['script-src']);
        $this->assertContains('ws://localhost:5173', $csp['connect-src']);
    }

    public function test_assets_from_the_central_domain_load_on_salon_subdomains(): void
    {
        config(['app.central_domain' => 'arisheh.test', 'app.url' => 'http://arisheh.test']);

        $csp = $this->policy('http://rasta.arisheh.test/');

        $this->assertContains('http://arisheh.test', $csp['script-src']);
        $this->assertContains('http://*.arisheh.test', $csp['img-src']);
    }

    public function test_enforce_and_off_modes(): void
    {
        config(['security.csp.mode' => 'enforce', 'security.csp.strict_scripts' => 'off']);
        $this->get('/')->assertHeader('Content-Security-Policy')->assertHeaderMissing('Content-Security-Policy-Report-Only');

        config(['security.csp.mode' => 'off']);
        $this->get('/')->assertHeaderMissing('Content-Security-Policy')->assertHeaderMissing('Content-Security-Policy-Report-Only');
    }

    /**
     * ⭐ ۲۰۲۶-۱۱-۰۴ (راه «الف»): داشبورد Telescope زیر CSP اجرایی سفید می‌ماند (`eval` ‏Vue، فونت bunny) ← فقط روی سیستم توسعه و فقط زیر
     * مسیر Telescope بی سیاست؛ بقیه‌ی صفحه‌های لوکال و هر محیط دیگر همان CSP.
     */
    public function test_only_the_local_telescope_dashboard_is_left_without_a_policy(): void
    {
        foreach (['telescope', 'telescope/requests', 'telescopex'] as $uri) {
            \Illuminate\Support\Facades\Route::get($uri, fn () => response('<!doctype html><title>t</title>', 200, ['Content-Type' => 'text/html; charset=UTF-8']));
        }

        // محیط تست (و سرور): Telescope هم سیاست دارد
        $this->get('/telescope/requests')->assertOk()->assertHeader('Content-Security-Policy');

        $this->app->detectEnvironment(fn () => 'local');

        $this->get('/telescope')->assertOk()->assertHeaderMissing('Content-Security-Policy')->assertHeaderMissing('Content-Security-Policy-Report-Only');
        $this->get('/telescope/requests')->assertOk()->assertHeaderMissing('Content-Security-Policy');
        $this->get('/telescopex')->assertOk()->assertHeader('Content-Security-Policy');
        $this->get('/')->assertOk()->assertHeader('Content-Security-Policy');

        config(['telescope.path' => 'dev/telescope']);
        $this->get('/telescope/requests')->assertOk()->assertHeader('Content-Security-Policy');
    }

    public function test_json_responses_carry_no_policy(): void
    {
        $this->getJson('/api/v1/status')->assertHeaderMissing('Content-Security-Policy-Report-Only')->assertHeaderMissing('Content-Security-Policy');
    }

    public function test_reports_in_both_browser_formats_are_logged_briefly(): void
    {
        $logged = [];
        Log::shouldReceive('channel')->with('csp')->andReturnSelf();
        Log::shouldReceive('info')->andReturnUsing(function ($message, $context) use (&$logged) {
            $logged[] = $context;
        });

        $this->call('POST', '/csp-report', [], [], [], ['CONTENT_TYPE' => 'application/csp-report'], json_encode([
            'csp-report' => ['document-uri' => 'https://arisheh.ir/', 'blocked-uri' => 'https://evil.example/x.js', 'violated-directive' => 'script-src', 'original-policy' => str_repeat('x', 5000)],
        ]))->assertNoContent();

        $this->call('POST', '/csp-report', [], [], [], ['CONTENT_TYPE' => 'application/reports+json'], json_encode([
            ['type' => 'csp-violation', 'body' => ['documentURL' => 'https://arisheh.ir/a', 'blockedURL' => 'inline', 'effectiveDirective' => 'style-src']],
            ['type' => 'deprecation', 'body' => ['id' => 'x']],
        ]))->assertNoContent();

        $this->call('POST', '/csp-report', [], [], [], [], 'not json')->assertNoContent();

        $this->assertCount(2, $logged);
        $this->assertSame('https://evil.example/x.js', $logged[0]['blocked-uri']);
        $this->assertArrayNotHasKey('original-policy', $logged[0], 'متن کامل سیاست لاگ نمی‌شود');
        $this->assertSame('style-src', $logged[1]['effective-directive']);
        $this->assertSame('inline', $logged[1]['blocked-uri']);
    }

    /**
     * گزارش ابوالفضل ۲۰۲۶-۱۰-۲۹: مشتری بعد از ثبت‌نام بیرون انداخته می‌شد و در هر صفحه «تعداد تلاش‌های شما بیش از حد مجاز است» می‌دید.
     * گزارش‌های CSP هم‌زمان با بار صفحه می‌رسند؛ با نشست، هر پاسخشان کوکی نشست قدیمی (پیش از regenerate ورود) را دوباره روی
     * مرورگر می‌نشاند و ۴۲۹ آن‌ها پیام خطا را در نشست کاربر flash می‌کرد. گزارش CSP نباید به نشست و کوکی دست بزند.
     */
    public function test_reports_never_touch_the_session_or_cookies_even_when_throttled(): void
    {
        $customer = \App\Models\User::factory()->create(['user_type' => 'customer']);
        $this->actingAs($customer);

        for ($i = 0; $i < 35; $i++) {
            $response = $this->call('POST', '/csp-report', [], [], [], ['CONTENT_TYPE' => 'application/csp-report'], json_encode([
                'csp-report' => ['document-uri' => 'http://127.0.0.1:8000/s/rasta', 'blocked-uri' => 'inline', 'violated-directive' => 'style-src'],
            ]));
            $this->assertSame([], $response->headers->getCookies(), "گزارش {$i}: بی Set-Cookie");
            $this->assertContains($response->getStatusCode(), [204, 429]);
        }

        $this->assertSame(429, $response->getStatusCode(), 'سقف همچنان هست');
        $this->assertNull(session('error'), 'پیام سقف در نشست کاربر نمی‌نشیند');
    }

    public function test_a_vite_dev_server_on_an_ipv6_loopback_is_allowed(): void
    {
        // Node 17+ روی ویندوز «localhost» را ::1 می‌کند و laravel-vite-plugin «http://[::1]:5173» در فایل hot می‌نویسد؛ CSP نشانی
        // IPv6 را نمی‌پذیرد، پس همه‌ی فایل‌های Vite گزارش می‌شدند (csp-2026-10-29.log ابوالفضل).
        $hot = Vite::hotFile();
        file_put_contents($hot, 'http://[::1]:5173');

        try {
            $csp = $this->policy();
        } finally {
            @unlink($hot);
        }

        // CSP برای نشانی IPv6 دستوری ندارد؛ فقط در حالت hot (توسعه) کل طرح http:/ws: باز می‌شود
        $this->assertNotContains('http://[::1]:5173', $csp['script-src'], 'CSP نشانی IPv6 را نمی‌فهمد');
        foreach (['script-src', 'style-src', 'font-src', 'connect-src'] as $directive) {
            $this->assertContains('http:', $csp[$directive], $directive);
        }
        $this->assertContains('ws:', $csp['connect-src']);
    }

    /** @return array<string, list<string>> */
    private static function parse(string $header): array
    {
        $directives = [];
        foreach (explode(';', $header) as $part) {
            $tokens = preg_split('/\s+/', trim($part));
            $directives[array_shift($tokens)] = $tokens;
        }

        return $directives;
    }

    /**
     * ⭐ بررسی امنیتی H3 مرحله‌ی ۲ (۲۰۲۶-۱۱-۰۵): پیش‌فرض strict_scripts=enforce — سیاست اجرایی script-src فقط nonce، بی
     * 'unsafe-inline' و بی هدر Report-Only دوم؛ همه‌ی <script>های درون‌خطی nonce همین درخواست را دارند.
     */
    public function test_scripts_are_nonce_only_and_enforced_by_default(): void
    {
        $response = $this->get('/')->assertOk();

        $response->assertHeaderMissing('Content-Security-Policy-Report-Only');
        $csp = self::parse((string) $response->headers->get('Content-Security-Policy'));
        $this->assertNotContains("'unsafe-inline'", $csp['script-src']);
        $nonces = array_values(array_filter($csp['script-src'], fn ($t) => str_starts_with($t, "'nonce-")));
        $this->assertCount(1, $nonces);

        $nonce = substr($nonces[0], 7, -1);
        preg_match_all('/<script(?![^>]*\bsrc=)([^>]*)>/i', (string) $response->getContent(), $inline);
        $this->assertNotEmpty($inline[1]);
        foreach ($inline[1] as $attrs) {
            $this->assertStringContainsString('nonce="'.$nonce.'"', $attrs, 'اسکریپت درون‌خطی بی nonce');
        }
    }

    /**
     * مرحله‌ی ۱ (۲۰۲۶-۱۱-۰۴) — حالا راه برگشت موقت (SECURITY_CSP_STRICT_SCRIPTS=report): سیاست اجرایی قبلی ('unsafe-inline')
     * و یک Report-Only با script-src فقط nonce. همه‌ی <script>های درون‌خطی صفحه nonce همین درخواست را دارند.
     */
    public function test_a_strict_nonce_policy_reports_alongside_the_enforced_one(): void
    {
        config(['security.csp.strict_scripts' => 'report']);
        $response = $this->get('/')->assertOk();

        $enforced = self::parse((string) $response->headers->get('Content-Security-Policy'));
        $strict = self::parse((string) $response->headers->get('Content-Security-Policy-Report-Only'));

        $this->assertContains("'unsafe-inline'", $enforced['script-src']);
        $this->assertNotContains("'unsafe-inline'", $strict['script-src']);
        $nonces = array_values(array_filter($strict['script-src'], fn ($t) => str_starts_with($t, "'nonce-")));
        $this->assertCount(1, $nonces);
        $this->assertSame(['/csp-report'], [parse_url($strict['report-uri'][0], PHP_URL_PATH)]);
        // nonce و 'unsafe-inline' هرگز با هم نیستند (nonce آن را بی‌اثر می‌کرد)
        $this->assertSame([], array_filter($enforced['script-src'], fn ($t) => str_starts_with($t, "'nonce-")));

        $nonce = substr($nonces[0], 7, -1);
        $html = $response->getContent();
        preg_match_all('/<script(?![^>]*\bsrc=)([^>]*)>/i', $html, $inline);
        $this->assertNotEmpty($inline[1], 'صفحه‌ی فروش اسکریپت درون‌خطی دارد');
        foreach ($inline[1] as $attrs) {
            $this->assertStringContainsString('nonce="'.$nonce.'"', $attrs, 'اسکریپت درون‌خطی بی nonce');
        }
    }

    public function test_each_request_gets_a_fresh_nonce(): void
    {
        $a = (string) $this->get('/')->headers->get('Content-Security-Policy');
        $this->app->forgetInstance(\Illuminate\Foundation\Vite::class);
        \Illuminate\Support\Facades\Vite::clearResolvedInstance(\Illuminate\Foundation\Vite::class);
        $b = (string) $this->get('/')->headers->get('Content-Security-Policy');

        preg_match("/'nonce-([^']+)'/", $a, $na);
        preg_match("/'nonce-([^']+)'/", $b, $nb);
        $this->assertNotSame($na[1], $nb[1]);
        $this->assertGreaterThanOrEqual(32, strlen($na[1]));
    }

    public function test_strict_enforce_drops_unsafe_inline_from_the_enforced_policy(): void
    {
        config(['security.csp.strict_scripts' => 'enforce']);
        $response = $this->get('/')->assertOk();

        $response->assertHeaderMissing('Content-Security-Policy-Report-Only');
        $csp = self::parse((string) $response->headers->get('Content-Security-Policy'));
        $this->assertNotContains("'unsafe-inline'", $csp['script-src']);
        $this->assertContains("'unsafe-inline'", $csp['style-src'], 'style همچنان درون‌خطی مجاز');
    }

    public function test_report_mode_with_strict_report_sends_a_single_strict_report_only_header(): void
    {
        config(['security.csp.mode' => 'report', 'security.csp.strict_scripts' => 'report']);
        $response = $this->get('/')->assertOk();

        $response->assertHeaderMissing('Content-Security-Policy');
        $this->assertStringContainsString("'nonce-", (string) $response->headers->get('Content-Security-Policy-Report-Only'));
    }

    public function test_every_inline_script_in_the_views_carries_the_nonce_directive(): void
    {
        $missing = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'))) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            preg_match_all('/<script(?![^>]*\bsrc=)([^>]*)>/i', file_get_contents($file->getPathname()), $m);
            foreach ($m[1] as $attrs) {
                if (! str_contains($attrs, '@nonce')) {
                    $missing[] = str_replace(resource_path('views').'/', '', $file->getPathname());
                }
            }
        }

        $this->assertSame([], $missing);
    }
}
