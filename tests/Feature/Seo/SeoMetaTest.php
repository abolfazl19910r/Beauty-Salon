<?php

namespace Tests\Feature\Seo;

use App\Models\BeautyService;
use App\Models\Salon;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * سئو (۲۰۲۶-۱۱-۲۳): noindex پیش‌فرض برای هر صفحه‌ی غیرعمومی (هدر و متا) و متای صفحه‌های عمومی (canonical، description، OG،
 * JSON-LD). بی CENTRAL_DOMAIN (سالن روی /s/{slug})؛ زیردامنه: SeoSubdomainTest.
 */
class SeoMetaTest extends TestCase
{
    use RefreshDatabase;

    private function salon(): Salon
    {
        return app(CurrentSalon::class)->get();
    }

    public function test_everything_is_noindex_when_indexing_is_disabled(): void
    {
        config(['seo.indexing' => false]);

        $this->get('/')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get('/s/rasta')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_landing_is_indexable_with_canonical_description_og_and_json_ld(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $this->assertFalse($response->headers->has('X-Robots-Tag'));
        $html = $response->getContent();
        $this->assertStringContainsString('<html lang="fa" dir="rtl"', $html);
        $this->assertStringContainsString('<link rel="canonical" href="'.$this->u().'">', $html);
        $this->assertSame(1, substr_count($html, '<meta name="description"'));
        $this->assertStringContainsString('<meta property="og:locale" content="fa_IR">', $html);
        $this->assertStringNotContainsString('noindex', $html);

        $ld = $this->jsonLd($html);
        $this->assertSame(['Organization', 'WebSite', 'SoftwareApplication'], array_column($ld, '@type'));
        $this->assertSame(1, substr_count($html, '"SoftwareApplication"'), 'JSON-LD قدیمی لندینگ نباید تکرار شود');
        $this->assertSame('IRR', $ld[2]['offers']['priceCurrency']);
        $this->assertGreaterThan(0, (int) $ld[2]['offers']['lowPrice']);
    }

    public function test_app_download_pages_have_their_own_title_and_description(): void
    {
        $html = $this->get('/app')->assertOk()->assertHeaderMissing('X-Robots-Tag')->getContent();
        $this->assertMatchesRegularExpression('#<title>دانلود اپ ماهرو برای اندروید \| [^<]+</title>#u', $html);
        $this->assertStringContainsString('<meta name="description" content="اپ اندروید ماهرو', $html);
        $this->assertStringContainsString('<link rel="canonical" href="'.$this->u().'/app">', $html);

        $html = $this->get('/app/staff')->assertOk()->assertHeaderMissing('X-Robots-Tag')->getContent();
        $this->assertStringContainsString('<link rel="canonical" href="'.$this->u().'/app/staff">', $html);
    }

    public function test_legal_pages_are_indexable_with_description(): void
    {
        foreach (['/terms', '/privacy', '/contact'] as $uri) {
            $html = $this->get($uri)->assertOk()->assertHeaderMissing('X-Robots-Tag')->getContent();
            $this->assertStringContainsString('<link rel="canonical" href="'.$this->u().''.$uri.'">', $html, $uri);
            $this->assertStringContainsString('<meta name="description"', $html, $uri);
        }
    }

    public static function privatePages(): array
    {
        return [
            'staff login' => ['/login'],
            'register' => ['/register'],
            'forgot password' => ['/forgot-password'],
            'my salons' => ['/my-salons'],
            'salon signup' => ['/salon-signup'],
            'admin (redirect)' => ['/admin'],
            'super admin (redirect)' => ['/superadmin/dashboard'],
            'specialist panel (redirect)' => ['/specialist/dashboard'],
            'salon login' => ['/s/rasta/login'],
            'salon bookings (redirect)' => ['/s/rasta/bookings'],
            'offline' => ['/offline'],
            'unknown page (404)' => ['/no-such-page'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('privatePages')]
    public function test_non_public_pages_are_noindex(string $uri): void
    {
        $response = $this->get($uri);

        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        if ($response->getStatusCode() === 200 && str_contains((string) $response->headers->get('Content-Type'), 'html')) {
            $this->assertStringNotContainsString('rel="canonical"', $response->getContent(), $uri);
        }
    }

    public function test_login_page_carries_a_robots_meta_too(): void
    {
        $this->get('/login')->assertOk()->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_api_responses_are_noindex(): void
    {
        $this->getJson('/api/v1/customer/me')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_salon_home_is_indexable_with_beauty_salon_json_ld(): void
    {
        $this->salon()->update(['phone' => '02112345678', 'address' => 'تهران، خیابان ولیعصر', 'tagline' => 'ناخن و مو با حوصله']);

        $response = $this->get('/s/rasta');

        $response->assertOk()->assertHeaderMissing('X-Robots-Tag');
        $html = $response->getContent();
        $this->assertStringContainsString('<link rel="canonical" href="'.$this->u().'/s/rasta">', $html);
        $this->assertStringContainsString('<meta name="description" content="ناخن و مو با حوصله">', $html);

        $salonLd = collect($this->jsonLd($html))->firstWhere('@type', 'BeautySalon');
        $this->assertNotNull($salonLd);
        $this->assertSame('سالن زیبایی راستا', $salonLd['name']);
        $this->assertSame('02112345678', $salonLd['telephone']);
        $this->assertSame('تهران، خیابان ولیعصر', $salonLd['address']['streetAddress']);
        $this->assertArrayNotHasKey('aggregateRating', $salonLd);
    }

    public function test_salon_service_page_uses_the_service_description(): void
    {
        $service = BeautyService::factory()->create(['description' => "کوتاهی مو\n با  مشاوره‌ی رایگان"]);

        $html = $this->get('/s/rasta/services/'.$service->id)->assertOk()->assertHeaderMissing('X-Robots-Tag')->getContent();

        $this->assertStringContainsString('<meta name="description" content="کوتاهی مو با مشاوره‌ی رایگان">', $html);
        $this->assertStringNotContainsString('BeautySalon', $html, 'JSON-LD سالن فقط روی خانه');
    }

    public function test_query_string_variants_keep_the_clean_canonical(): void
    {
        $html = $this->get('/s/rasta/services?category=3&page=2')->assertOk()->getContent();

        $this->assertStringContainsString('<link rel="canonical" href="'.$this->u().'/s/rasta/services">', $html);
    }

    public function test_json_ld_cannot_break_out_of_its_script_tag(): void
    {
        $this->salon()->update(['tagline' => '</script><script>alert(1)</script>']);

        $html = $this->get('/s/rasta')->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertNotEmpty(collect($this->jsonLd($html))->firstWhere('@type', 'BeautySalon'));
    }

    private function u(string $path = ''): string
    {
        return rtrim((string) config('app.url'), '/').$path;
    }

    /** @return list<array<string, mixed>> */
    private function jsonLd(string $html): array
    {
        preg_match_all('#<script type="application/ld\+json"[^>]*>(.*?)</script>#s', $html, $m);

        return array_map(fn ($json) => json_decode($json, true, flags: JSON_THROW_ON_ERROR), $m[1]);
    }
}
