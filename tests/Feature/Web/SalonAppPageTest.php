<?php

namespace Tests\Feature\Web;

use App\Models\AppRelease;
use App\Models\Salon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * صفحه‌ی «اپ» هر سالن (/app، بسته‌ی ۵ و ۶): اندروید ← دانلود APK ماهرو + کد سالن، آیفون ← راهنمای افزودن به صفحه‌ی اصلی،
 * کامپیوتر ← QR همین صفحه.
 */
class SalonAppPageTest extends TestCase
{
    use RefreshDatabase;

    private const ANDROID = 'Mozilla/5.0 (Linux; Android 14; SM-A175F) AppleWebKit/537.36 Chrome/130.0 Mobile Safari/537.36';

    private const IPHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 Version/17.5 Mobile/15E148 Safari/604.1';

    private function release(): AppRelease
    {
        return AppRelease::create([
            'variant' => 'customer', 'version_name' => '0.3.0', 'version_code' => 8, 'file_path' => 'app-releases/customer/x.apk',
            'file_size' => 40 * 1048576, 'sha256' => str_repeat('a', 64), 'is_published' => true,
        ]);
    }

    public function test_android_sees_download_open_in_app_and_salon_code(): void
    {
        $html = $this->get('/s/rasta/app', ['User-Agent' => self::ANDROID])->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/data-install-section="android"\s*>/', $html);
        $this->assertMatchesRegularExpression('/data-install-section="ios"\s+hidden/', $html);
        $this->assertStringContainsString('data-install-soon', $html);
        $this->assertStringContainsString('intent://salon/rasta#Intent;', $html);
        $this->assertStringContainsString('data-install-salon-code', $html);

        $this->release();
        $this->get('/s/rasta/app', ['User-Agent' => self::ANDROID])->assertOk()
            ->assertSee('data-install-download', false)
            ->assertSee('href="'.url('/app/download/customer').'"', false)
            ->assertSee(url('/app').'?salon=rasta', false);
    }

    public function test_iphone_sees_the_home_screen_guide(): void
    {
        $html = $this->get('/s/rasta/app', ['User-Agent' => self::IPHONE])->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/data-install-section="ios"\s*>/', $html);
        $this->assertMatchesRegularExpression('/data-install-section="android"\s+hidden/', $html);
        $this->assertStringContainsString('Add to Home Screen', $html);
    }

    public function test_desktop_sees_a_qr_of_this_page(): void
    {
        $this->get('/s/rasta/app', ['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'])->assertOk()
            ->assertSee('data-salon-qr="'.url('/s/rasta/app').'"', false);
    }

    public function test_each_salon_has_its_own_page_and_suspended_salon_is_hidden(): void
    {
        Salon::factory()->create(['slug' => 'golbahar', 'name' => 'گلبهار']);
        Salon::factory()->create(['slug' => 'baste', 'is_suspended' => true]);

        $this->get('/s/golbahar/app', ['User-Agent' => self::ANDROID])->assertOk()->assertSee('intent://salon/golbahar#', false)
            ->assertDontSee('intent://salon/rasta#', false);
        $this->get('/s/baste/app')->assertNotFound();
    }
}
