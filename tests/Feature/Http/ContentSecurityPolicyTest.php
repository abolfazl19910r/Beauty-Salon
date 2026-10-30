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
        $this->assertContains("'unsafe-inline'", $csp['script-src'], 'اسکریپت‌های درون‌خطی صفحه‌ها در مرحله‌ی ۱ می‌مانند');
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
        config(['security.csp.mode' => 'enforce']);
        $this->get('/')->assertHeader('Content-Security-Policy')->assertHeaderMissing('Content-Security-Policy-Report-Only');

        config(['security.csp.mode' => 'off']);
        $this->get('/')->assertHeaderMissing('Content-Security-Policy')->assertHeaderMissing('Content-Security-Policy-Report-Only');
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
}
