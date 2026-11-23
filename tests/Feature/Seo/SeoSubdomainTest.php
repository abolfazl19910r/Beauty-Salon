<?php

namespace Tests\Feature\Seo;

use App\Models\Salon;
use App\Support\CentralUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * سئو با CENTRAL_DOMAIN پر (شکل production، arisheh.ir): هر زیردامنه‌ی سالن robots و sitemap خودش را دارد، sitemap index
 * دامنه‌ی اصلی به sitemap زیردامنه‌ها اشاره می‌کند و صفحه‌های پلتفرم روی زیردامنه canonical/index نمی‌گیرند.
 * فقط با phpunit.subdomain.xml (CENTRAL_DOMAIN=rasta-test.local) — به docblock SubdomainRoutingTest نگاه کن.
 */
class SeoSubdomainTest extends TestCase
{
    use RefreshDatabase;

    private const CENTRAL = 'rasta-test.local';

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(self::CENTRAL, config('app.central_domain'), 'با phpunit.subdomain.xml اجرا کنید');
    }

    public function test_central_robots_points_to_the_central_sitemap(): void
    {
        $body = $this->get('http://'.self::CENTRAL.'/robots.txt')->assertOk()->getContent();

        $this->assertStringContainsString('Disallow: /admin', $body);
        $this->assertStringContainsString('Sitemap: '.CentralUrl::to('/sitemap.xml')."\n", $body);
    }

    public function test_salon_subdomain_has_its_own_robots_and_sitemap(): void
    {
        Salon::factory()->create(['slug' => 'sobhan-beauty']);
        $host = 'http://sobhan-beauty.'.self::CENTRAL;

        $robots = $this->get($host.'/robots.txt')->assertOk()->getContent();
        $this->assertStringContainsString("Disallow: /bookings\n", $robots);
        $this->assertStringNotContainsString('Disallow: /admin', $robots);
        $this->assertStringContainsString('Sitemap: '.$host.'/sitemap.xml', $robots);

        $sitemap = $this->get($host.'/sitemap.xml')->assertOk()->getContent();
        $this->assertStringContainsString('<loc>'.$host.'</loc>', $sitemap);
        $this->assertStringContainsString('<loc>'.$host.'/services</loc>', $sitemap);
    }

    public function test_central_sitemap_index_points_to_salon_subdomain_sitemaps(): void
    {
        Salon::factory()->create(['slug' => 'sobhan-beauty']);
        Salon::factory()->unverified()->create(['slug' => 'new-salon']);

        $xml = $this->get('http://'.self::CENTRAL.'/sitemap.xml')->assertOk()->getContent();

        $this->assertStringContainsString('<loc>'.CentralUrl::to('/sitemap-pages.xml').'</loc>', $xml);
        $this->assertStringContainsString('<loc>http://sobhan-beauty.'.self::CENTRAL.'/sitemap.xml</loc>', $xml);
        $this->assertStringNotContainsString('new-salon', $xml);
    }

    public function test_salon_home_on_subdomain_is_indexable_with_subdomain_canonical(): void
    {
        Salon::factory()->create(['slug' => 'sobhan-beauty']);
        $host = 'http://sobhan-beauty.'.self::CENTRAL;

        $html = $this->get($host.'/')->assertOk()->assertHeaderMissing('X-Robots-Tag')->getContent();

        $this->assertStringContainsString('<link rel="canonical" href="'.$host.'">', $html);
        $this->assertStringContainsString('"@type":"BeautySalon"', $html);
    }

    public function test_platform_pages_reached_on_a_salon_subdomain_are_noindex(): void
    {
        Salon::factory()->create(['slug' => 'sobhan-beauty']);

        // /privacy route بی‌دامنه است و روی هر میزبانی جواب می‌دهد؛ نسخه‌ی اصلی فقط روی دامنه‌ی اصلی ایندکس شود
        $this->get('http://sobhan-beauty.'.self::CENTRAL.'/privacy')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get('http://'.self::CENTRAL.'/privacy')->assertOk()->assertHeaderMissing('X-Robots-Tag');
    }

    public function test_legacy_salon_path_on_central_domain_is_not_an_indexable_duplicate(): void
    {
        Salon::factory()->create(['slug' => 'sobhan-beauty']);

        $this->get('http://'.self::CENTRAL.'/s/sobhan-beauty')->assertRedirect()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }
}
