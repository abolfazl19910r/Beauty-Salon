<?php

namespace Tests\Feature\Web;

use App\Models\Salon;
use App\Support\CustomerAppLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * دکمه‌ی «باز کردن در اپ ماهرو» صفحه‌ی وب سالن (بسته‌ی ۳ب، تصمیم ۲۰۲۶-۱۰-۱۴): لینک intent اندروید به mahru://salon/{slug}
 * همان سالن، با برگشت به آدرس دانلود (اگر تنظیم شده) یا همین صفحه.
 */
class OpenInAppBannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_salon_page_links_to_the_same_salon_in_the_app(): void
    {
        $rasta = Salon::where('slug', 'rasta')->firstOrFail();
        $fallback = rawurlencode($rasta->publicUrl());

        $this->get('/s/rasta')
            ->assertOk()
            ->assertSee('data-open-in-app', false)
            ->assertSee("intent://salon/rasta#Intent;scheme=mahru;package=ir.mahru;S.browser_fallback_url={$fallback};end", false)
            ->assertDontSee('data-open-in-app-download', false);
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

    public function test_with_a_download_url_the_banner_offers_it_and_android_falls_back_to_it(): void
    {
        config(['api.customer_app_download_url' => 'https://mahru.ir/app/mahru.apk']);

        $this->get('/s/rasta')
            ->assertOk()
            ->assertSee('data-open-in-app-download', false)
            ->assertSee('href="https://mahru.ir/app/mahru.apk"', false)
            ->assertSee('S.browser_fallback_url='.rawurlencode('https://mahru.ir/app/mahru.apk').';end', false);
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
