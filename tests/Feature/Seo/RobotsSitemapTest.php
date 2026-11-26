<?php

namespace Tests\Feature\Seo;

use App\Models\BeautyService;
use App\Models\BlogPost;
use App\Models\Salon;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * سئو (۲۰۲۶-۱۱-۲۳): robots.txt و sitemap پویا برای دامنه‌ی اصلی و هر سالن. بی CENTRAL_DOMAIN (سالن روی /s/{slug})؛
 * زیردامنه: SeoSubdomainTest.
 */
class RobotsSitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_robots_txt_allows_public_pages_blocks_panels_and_points_to_the_sitemap(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $this->assertStringStartsWith('text/plain', $response->headers->get('Content-Type'));
        $body = $response->getContent();
        $this->assertStringContainsString("User-agent: *\nAllow: /\n", $body);
        foreach (['/admin', '/superadmin', '/api/', '/specialist/'] as $path) {
            $this->assertStringContainsString("Disallow: {$path}\n", $body);
        }
        $this->assertStringNotContainsString("Disallow: /\n", $body);
        $this->assertStringNotContainsString('/login', $body, 'صفحه‌ی ورود باید خزیده شود تا noindex آن دیده شود');
        $this->assertStringContainsString('Sitemap: '.$this->u('/sitemap.xml'), $body);
    }

    public function test_static_robots_file_is_gone_so_every_host_gets_its_own(): void
    {
        $this->assertFileDoesNotExist(public_path('robots.txt'));
    }

    public function test_robots_blocks_everything_when_indexing_is_disabled(): void
    {
        config(['seo.indexing' => false]);

        $this->assertSame("User-agent: *\nDisallow: /\n", $this->get('/robots.txt')->assertOk()->getContent());
        $this->get('/sitemap.xml')->assertNotFound();
    }

    public function test_sitemap_index_lists_platform_pages_and_only_public_salons(): void
    {
        Salon::factory()->create(['slug' => 'open-salon']);
        Salon::factory()->suspended()->create(['slug' => 'closed-salon']);
        Salon::factory()->expired()->create(['slug' => 'expired-salon']);
        Salon::factory()->unverified()->create(['slug' => 'new-salon']);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $this->assertStringStartsWith('application/xml', $response->headers->get('Content-Type'));
        $xml = simplexml_load_string($response->getContent());
        $this->assertSame('sitemapindex', $xml->getName());
        $locs = array_map('strval', iterator_to_array($xml->xpath('//*[local-name()="loc"]'), false));
        $this->assertContains($this->u('/sitemap-pages.xml'), $locs);
        $this->assertContains($this->u('/s/rasta/sitemap.xml'), $locs);
        $this->assertContains($this->u('/s/open-salon/sitemap.xml'), $locs);
        foreach (['closed-salon', 'expired-salon', 'new-salon'] as $hidden) {
            $this->assertEmpty(array_filter($locs, fn ($l) => str_contains($l, $hidden)), $hidden);
        }
    }

    public function test_platform_pages_sitemap_has_only_public_central_pages(): void
    {
        $locs = $this->locs($this->get('/sitemap-pages.xml')->assertOk()->getContent());

        $this->assertSame([
            $this->u(''), $this->u('/app'), $this->u('/app/staff'),
            $this->u('/terms'), $this->u('/privacy'), $this->u('/contact'),
        ], $locs);
    }

    public function test_salon_sitemap_lists_public_salon_pages_and_published_posts_only(): void
    {
        $service = BeautyService::factory()->create();
        $post = BlogPost::factory()->create();
        $draft = BlogPost::factory()->unpublished()->create();
        $scheduled = BlogPost::factory()->scheduled()->create();

        $locs = $this->locs($this->get('/s/rasta/sitemap.xml')->assertOk()->getContent());

        $this->assertContains($this->u('/s/rasta'), $locs);
        $this->assertContains($this->u('/s/rasta/services'), $locs);
        $this->assertContains($this->u('/s/rasta/services/').$service->id, $locs);
        $this->assertContains($this->u('/s/rasta/blog/').$post->slug, $locs);
        $this->assertNotContains($this->u('/s/rasta/blog/').$draft->slug, $locs);
        $this->assertNotContains($this->u('/s/rasta/blog/').$scheduled->slug, $locs);
        $this->assertEmpty(array_filter($locs, fn ($l) => str_contains($l, '/login') || str_contains($l, '/bookings')));
    }

    public function test_salon_sitemap_never_lists_another_salons_content(): void
    {
        $other = Salon::factory()->create(['slug' => 'other-salon']);
        app(CurrentSalon::class)->set($other);
        $foreign = BeautyService::factory()->create();
        app(CurrentSalon::class)->set(Salon::where('slug', 'rasta')->first());

        $locs = $this->locs($this->get('/s/rasta/sitemap.xml')->assertOk()->getContent());

        $this->assertNotContains($this->u('/s/rasta/services/').$foreign->id, $locs);
    }

    public function test_salon_robots_and_sitemap_are_closed_for_a_salon_that_is_not_public(): void
    {
        Salon::factory()->unverified()->create(['slug' => 'new-salon']);

        // بسته‌ی ۷: صفحه‌ی سالن احرازنشده باز است ولی ایندکس نمی‌شود
        $this->get('/s/new-salon/sitemap.xml')->assertNotFound();
        $this->get('/s/new-salon/robots.txt')->assertOk()->assertSee("Disallow: /\n", false);
    }

    public function test_salon_robots_txt_points_to_its_own_sitemap(): void
    {
        $body = $this->get('/s/rasta/robots.txt')->assertOk()->getContent();

        $this->assertStringContainsString("Disallow: /bookings\n", $body);
        $this->assertStringContainsString('Sitemap: '.$this->u('/s/rasta/sitemap.xml'), $body);
    }

    private function u(string $path = ''): string
    {
        return rtrim((string) config('app.url'), '/').$path;
    }

    /** @return list<string> */
    private function locs(string $xml): array
    {
        $doc = simplexml_load_string($xml);

        return array_map('strval', iterator_to_array($doc->xpath('//*[local-name()="loc"]'), false));
    }
}
