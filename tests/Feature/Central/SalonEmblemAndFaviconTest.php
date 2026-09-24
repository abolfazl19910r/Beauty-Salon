<?php

namespace Tests\Feature\Central;

use App\Models\Specialist;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ ۲۰۲۶-۰۹-۲۵ — آیکون قلب قدیمی از ورود/ثبت‌نام و سربرگ‌ها حذف شد: سالنی که لوگو داره لوگوی خودش رو
 * (در صفحه و در تب مرورگر) می‌بینه، سالن بدون لوگو نشان ماهرو رو. پنل مدیر و متخصص هم قبلاً favicon
 * ثابت داشتن و لوگوی سالن رو نادیده می‌گرفتن.
 */
class SalonEmblemAndFaviconTest extends TestCase
{
    use RefreshDatabase;

    private const HEART = 'M20.84 4.61a5.5';

    private function withLogo(): string
    {
        $salon = app(CurrentSalon::class)->get();
        $salon->update(['logo_path' => "salons/{$salon->id}/branding/logo.png"]);

        return "salons/{$salon->id}/branding/logo.png";
    }

    private function withoutLogo(): void
    {
        app(CurrentSalon::class)->get()->update(['logo_path' => null]);
    }

    public function test_login_and_register_pages_show_the_mahru_mark_when_the_salon_has_no_logo(): void
    {
        $this->withoutLogo();
        $slug = app(CurrentSalon::class)->get()->slug;

        // /register خودش به /s/{slug}/register ریدایرکت می‌کنه
        foreach (['/login', "/s/{$slug}/login", "/s/{$slug}/register"] as $url) {
            $this->get($url)->assertOk()
                ->assertSee('brand/mahru-mark.svg', false)
                ->assertSee('favicon.svg', false)
                ->assertDontSee(self::HEART, false);
        }
    }

    public function test_login_and_register_pages_use_the_salons_own_logo_in_the_page_and_the_tab(): void
    {
        $logo = $this->withLogo();
        $slug = app(CurrentSalon::class)->get()->slug;

        foreach (['/login', "/s/{$slug}/register"] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertMatchesRegularExpression('#<link rel="icon" href="[^"]*'.preg_quote($logo, '#').'" type="image/png"#', $html);
            $this->assertStringContainsString('<link rel="apple-touch-icon" href="', $html);
            $this->assertStringNotContainsString('favicon.svg', $html);
            $this->assertStringNotContainsString('brand/mahru-mark.svg', $html);
        }
    }

    public function test_customer_site_header_uses_the_mahru_mark_as_fallback(): void
    {
        $this->withoutLogo();

        $this->get('/s/'.app(CurrentSalon::class)->get()->slug)->assertOk()
            ->assertSee('brand/mahru-mark.svg', false)
            ->assertDontSee(self::HEART, false);
    }

    public function test_admin_panel_tab_and_sidebar_follow_the_salon_logo(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);

        $this->withoutLogo();
        $this->actingAs($owner)->get(route('admin.dashboard'))->assertOk()
            ->assertSee('favicon.svg', false)->assertSee('brand/mahru-mark-on-light.svg', false);

        $logo = $this->withLogo();
        $html = $this->actingAs($owner)->get(route('admin.dashboard'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('#<link rel="icon" href="[^"]*'.preg_quote($logo, '#').'"#', $html);
        $this->assertStringNotContainsString('favicon.svg', $html);
    }

    public function test_specialist_panel_tab_follows_the_salon_logo(): void
    {
        $specialist = Specialist::factory()->create();
        $user = User::where('phone', $specialist->phone)->firstOrFail();
        $logo = $this->withLogo();

        $html = $this->actingAs($user)->get(route('specialist.my-dashboard'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('#<link rel="icon" href="[^"]*'.preg_quote($logo, '#').'"#', $html);
        $this->assertStringNotContainsString(self::HEART, $html);
    }
}
