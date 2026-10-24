<?php

namespace Tests\Feature\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * هدرهای امنیتی در خود برنامه (بسته‌ی مجوز 5د، ۲۰۲۶-۱۰-۲۵).
 *
 * 🐞 باگ: App\Http\Middleware\SecurityMiddleware نوشته شده بود ولی هیچ‌جا ثبت نشده بود؛ Referrer-Policy، Permissions-Policy
 * و HSTS هیچ‌وقت فرستاده نمی‌شد و X-Frame-Options/nosniff فقط از nginx کانتینر می‌آمد (روی XAMPP، DirectAdmin و هر
 * میزبان دیگری نبود). قاعده: امنیت نباید به CDN یا یک میزبان خاص وابسته باشد.
 */
class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_pages_carry_the_security_headers(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString('camera=()', (string) $response->headers->get('Permissions-Policy'));
    }

    public function test_api_and_error_responses_carry_them_too(): void
    {
        $this->getJson('/api/v1/status')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get('/no-such-page-'.uniqid())->assertNotFound()->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_hsts_is_sent_only_over_https(): void
    {
        $this->get('/')->assertHeaderMissing('Strict-Transport-Security');

        $this->get('https://localhost/')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_hsts_follows_https_seen_through_a_trusted_proxy_only(): void
    {
        config(['trustedproxy.proxies' => '10.0.0.1']);

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('/')->assertHeader('Strict-Transport-Security');
    }

    public function test_a_forged_forwarded_proto_does_not_produce_hsts(): void
    {
        config(['trustedproxy.proxies' => '10.0.0.1']);

        $this->withServerVariables(['REMOTE_ADDR' => '5.5.5.5'])->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('/')->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_hsts_can_be_turned_off(): void
    {
        config(['security.hsts.max_age' => 0]);

        $this->get('https://localhost/')->assertHeaderMissing('Strict-Transport-Security');
    }
}
