<?php

namespace Tests\Feature\Web;

use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * بسته‌ی ۶ (۲۰۲۶-۱۰-۲۰): نصب سایت سالن و پنل متخصص روی آیفون (PWA) — manifest، آیکون از لوگو، service worker، صفحه‌ی آفلاین،
 * بنر و راهنمای نصب.
 */
class PwaTest extends TestCase
{
    use RefreshDatabase;

    private Salon $salon;

    protected function setUp(): void
    {
        parent::setUp();
        $this->salon = app(CurrentSalon::class)->get();
    }

    public function test_salon_manifest(): void
    {
        $response = $this->get('/s/rasta/manifest.webmanifest')->assertOk();
        $this->assertStringStartsWith('application/manifest+json', (string) $response->headers->get('Content-Type'));

        $response->assertJson([
            'lang' => 'fa', 'dir' => 'rtl', 'display' => 'standalone',
            'id' => '/s/rasta/', 'scope' => '/s/rasta/', 'start_url' => '/s/rasta/?source=pwa',
            'name' => \App\Support\SalonTitle::for($this->salon->name),
        ]);
        $this->assertSame(['/s/rasta/pwa/icon-192.png', '/s/rasta/pwa/icon-512.png', '/s/rasta/pwa/icon-maskable-512.png'], array_column($response->json('icons'), 'src'));
        $this->assertMatchesRegularExpression('/^#[0-9A-F]{6}$/', $response->json('theme_color'));

        // نام زیر آیکون آیفون بی پیشوند «سالن زیبایی» (۲۰۲۶-۱۰-۲۱)
        $this->salon->update(['name' => 'سالن زیبایی راستا']);
        $this->get('/s/rasta/manifest.webmanifest')->assertJson(['short_name' => 'راستا']);
        $this->get('/s/rasta')->assertSee('<meta name="apple-mobile-web-app-title" content="راستا">', false);
    }

    public function test_icon_falls_back_to_mahru_and_is_built_from_the_salon_logo(): void
    {
        $file = $this->get('/s/rasta/pwa/icon-180.png')->assertOk()->baseResponse->getFile()->getPathname();
        $this->assertSame(realpath(public_path('apple-touch-icon.png')), realpath($file));

        Storage::fake('public');
        $logo = imagecreatetruecolor(300, 120);
        imagefill($logo, 0, 0, imagecolorallocate($logo, 200, 30, 30));
        ob_start();
        imagepng($logo);
        Storage::disk('public')->put('salon-logos/rasta.png', (string) ob_get_clean());
        $this->salon->update(['logo_path' => 'salon-logos/rasta.png']);
        $this->assertNotNull($this->salon->fresh()->logoUrl());

        foreach (['180' => 180, '192' => 192, 'maskable-512' => 512] as $variant => $size) {
            $file = $this->get("/s/rasta/pwa/icon-{$variant}.png")->assertOk()->baseResponse->getFile()->getPathname();
            [$w, $h] = getimagesize($file);
            $this->assertSame([$size, $size], [$w, $h]);
            $this->assertStringContainsString('pwa-icons/'.$this->salon->id.'/', str_replace('\\', '/', $file));
        }
        $this->assertCount(3, Storage::disk('public')->allFiles('pwa-icons/'.$this->salon->id));

        // لوگوی تازه ← آیکون تازه
        Storage::disk('public')->put('salon-logos/rasta2.png', Storage::disk('public')->get('salon-logos/rasta.png'));
        $this->salon->update(['logo_path' => 'salon-logos/rasta2.png']);
        $this->get('/s/rasta/pwa/icon-192.png')->assertOk();
        $this->assertCount(4, Storage::disk('public')->allFiles('pwa-icons/'.$this->salon->id));

        $this->get('/s/rasta/pwa/icon-64.png')->assertNotFound();
    }

    public function test_salon_pages_link_the_manifest_and_offer_the_iphone_install(): void
    {
        $this->get('/s/rasta')->assertOk()
            ->assertSee('data-pwa-manifest="salon"', false)
            ->assertSee(url('/s/rasta/manifest.webmanifest'), false)
            ->assertSee('apple-mobile-web-app-capable', false)
            ->assertSee(url('/s/rasta/pwa/icon-180.png'), false)
            ->assertSee('data-ios-install-banner hidden', false)
            ->assertSee(url('/s/rasta/app'), false)
            ->assertSee("navigator.serviceWorker.register('/sw.js')", false)
            ->assertSee('beforeinstallprompt', false);

        // صفحه‌ی ورود سالن: manifest و service worker هست، بنر نه
        $this->get('/s/rasta/login')->assertOk()->assertSee('data-pwa-manifest="salon"', false)
            ->assertSee("navigator.serviceWorker.register('/sw.js')", false)->assertDontSee('data-ios-install-banner hidden', false);
    }

    public function test_admin_panel_has_no_pwa_manifest(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))->get(route('admin.dashboard'))->assertOk()
            ->assertDontSee('data-pwa-manifest', false);
    }

    public function test_specialist_panel_uses_the_public_specialist_manifest_and_guide(): void
    {
        $user = User::factory()->create(['phone' => '09121234567']);
        Specialist::factory()->create(['phone' => '09121234567', 'user_id' => $user->id]);

        $this->actingAs($user)->get(route('specialist.my-dashboard'))->assertOk()
            ->assertSee('data-pwa-manifest="specialist"', false)
            ->assertSee(url('/s/rasta/pwa/icon-180.png'), false)
            ->assertSee(route('specialist.install'), false);
        $this->get(route('specialist.install'))->assertOk()->assertSee('data-ios-steps', false)->assertSee('data-specialist-install', false);

        auth()->logout();
        $this->get('/specialist/manifest.webmanifest')->assertOk()
            ->assertJson(['id' => '/specialist/', 'scope' => '/', 'start_url' => '/my-dashboard?source=pwa', 'short_name' => 'پنل متخصص']);
    }

    public function test_service_worker_and_offline_page(): void
    {
        $response = $this->get('/sw.js')->assertOk();
        $this->assertStringStartsWith('application/javascript', (string) $response->headers->get('Content-Type'));
        $this->assertSame('/', $response->headers->get('Service-Worker-Allowed'));
        $this->assertStringNotContainsString('__VERSION__', $response->getContent());
        $this->assertMatchesRegularExpression("/const CACHE = 'mahru-[0-9a-f]{12}';/", $response->getContent());

        $this->get('/offline')->assertOk()->assertSee('اتصال به اینترنت برقرار نیست');
    }

    public function test_service_worker_is_not_registered_while_vite_dev_server_runs_or_when_disabled(): void
    {
        config(['pwa.service_worker' => false]);
        $this->get('/s/rasta')->assertOk()->assertDontSee("navigator.serviceWorker.register('/sw.js')", false);

        config(['pwa.service_worker' => true]);
        file_put_contents(public_path('hot'), 'http://127.0.0.1:5173');
        try {
            $this->get('/s/rasta')->assertOk()->assertDontSee("navigator.serviceWorker.register('/sw.js')", false);
        } finally {
            @unlink(public_path('hot'));
        }
    }
}
