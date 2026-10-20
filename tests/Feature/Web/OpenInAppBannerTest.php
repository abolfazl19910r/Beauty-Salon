<?php

namespace Tests\Feature\Web;

use App\Models\Salon;
use App\Support\CustomerAppLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * دکمه‌ی «باز کردن در اپ ماهرو» صفحه‌ی وب سالن (بسته‌ی ۳ب، تصمیم ۲۰۲۶-۱۰-۱۴): لینک intent اندروید به mahru://salon/{slug}
 * همان سالن، با برگشت به صفحه‌ی «اپ» همان سالن (/app، بسته‌ی ۵) که دانلود APK و کد سالن را دارد.
 */
class OpenInAppBannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_salon_page_links_to_the_same_salon_in_the_app(): void
    {
        $fallback = rawurlencode(url('/s/rasta/app'));

        $this->get('/s/rasta')
            ->assertOk()
            ->assertSee('data-open-in-app', false)
            ->assertSee("intent://salon/rasta#Intent;scheme=mahru;package=ir.mahru;S.browser_fallback_url={$fallback};end", false)
            ->assertSee('data-open-in-app-install', false)
            ->assertSee('href="'.url('/s/rasta/app').'"', false);
    }

    public function test_each_salon_page_points_to_its_own_salon(): void
    {
        Salon::factory()->create(['slug' => 'golbahar', 'name' => 'گلبهار']);

        $this->get('/s/golbahar')
            ->assertOk()
            ->assertSee('intent://salon/golbahar#Intent;', false)
            ->assertDontSee('intent://salon/rasta#', false)
            ->assertSee('«گلبهار» در اپ ماهرو', false);
    }

    public function test_download_url_override_is_used_on_the_install_page(): void
    {
        config(['api.customer_app_download_url' => 'https://mahru.ir/app/mahru.apk']);

        $this->get('/s/rasta/app', ['User-Agent' => 'Mozilla/5.0 (Linux; Android 14)'])
            ->assertOk()
            ->assertSee('href="https://mahru.ir/app/mahru.apk"', false);
    }

    public function test_link_parts_follow_the_config_and_encode_the_slug(): void
    {
        config(['api.customer_app_scheme' => 'mahru-test', 'api.customer_app_package' => 'ir.mahru.test']);
        $salon = Salon::factory()->make(['slug' => 'مهتاب']);

        $link = CustomerAppLink::forSalon($salon);

        $this->assertSame('mahru-test://salon/'.rawurlencode('مهتاب'), $link['app_url']);
        $this->assertStringStartsWith('intent://salon/'.rawurlencode('مهتاب').'#Intent;scheme=mahru-test;package=ir.mahru.test;', $link['intent_url']);
        $this->assertNull($link['download_url']);
    }
}
