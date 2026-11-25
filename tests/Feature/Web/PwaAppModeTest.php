<?php

namespace Tests\Feature\Web;

use App\Models\Plan;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Services\Plans\DefaultPlans;
use App\Support\CurrentSalon;
use App\Support\Themes\SalonAppearance;
use App\Support\Themes\ThemeCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * بسته‌ی ۵ج (۲۰۲۶-۱۱-۲۶): حالت اپ در مرورگر گوشی — ناحیه‌ی امن، منوی پایین مهمان، بنر «باز کردن در اپ» در همه‌ی قالب‌ها،
 * دکمه‌ی «برگشت» و پانویس کوتاه در حالت نصب‌شده، رنگ نوار وضعیت و صفحه‌ی آفلاین با پالت سالن.
 */
class PwaAppModeTest extends TestCase
{
    use RefreshDatabase;

    private Salon $salon;

    protected function setUp(): void
    {
        parent::setUp();
        app(DefaultPlans::class)->sync();
        $this->salon = app(CurrentSalon::class)->get();
    }

    private function useTemplate(string $site): void
    {
        $this->salon->forceFill([
            'site_theme' => $site, 'plan_id' => Plan::byKey('clinic')->id, 'subscription_type' => '12m', 'trial_ends_at' => null,
            'subscription_ends_at' => now()->addYear(),
        ])->save();
        SalonAppearance::flush();
        Cache::flush();
    }

    private function bottomNav(string $html, string $attr): ?string
    {
        return preg_match('/<nav[^>]*'.$attr.'[^>]*>(.*?)<\/nav>/s', $html, $m) ? $m[1] : null;
    }

    public function test_salon_site_and_panels_cover_the_safe_area(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();
        $this->assertStringContainsString('viewport-fit=cover', $html);
        $this->assertStringContainsString("d.classList.add('is-standalone')", $html);
        $this->assertStringContainsString('m-bottom-nav', $html);

        $this->get(route('salon.login'))->assertOk()->assertSee('viewport-fit=cover', false);

        $user = User::factory()->create(['phone' => '09121234567']);
        Specialist::factory()->create(['phone' => '09121234567', 'user_id' => $user->id]);
        $panel = $this->actingAs($user)->get(route('specialist.my-dashboard'))->assertOk()->getContent();
        $this->assertStringContainsString('viewport-fit=cover', $panel);
        $this->assertMatchesRegularExpression('/<body class="[^"]*m-nav-offset/', $panel);
        $this->assertMatchesRegularExpression('/<nav[^>]*m-bottom-nav[^>]*data-spec-bottom/', $panel);
        $this->assertStringContainsString('m-pb-nav', $panel);
    }

    public function test_guests_get_their_own_bottom_menu(): void
    {
        $html = $this->get(route('services.index'))->assertOk()->getContent();
        $nav = $this->bottomNav($html, 'data-guest-bottom');
        $this->assertNotNull($nav);
        foreach (['خانه' => route('home'), 'خدمات' => route('services.index'), 'رزرو' => route('bookings.create'), 'کارشناسان' => route('specialists.search'), 'ورود' => route('salon.login')] as $label => $url) {
            $this->assertStringContainsString($label, $nav);
            $this->assertStringContainsString('href="'.$url.'"', $nav);
        }
        $this->assertMatchesRegularExpression('/href="'.preg_quote(route('services.index'), '/').'"\s+aria-current="page"/', $nav);
        $this->assertMatchesRegularExpression('/<body class="[^"]*m-has-bottom-nav/', $html);
        $this->assertStringNotContainsString('data-customer-bottom', $html);
    }

    public function test_customers_keep_their_menu_and_staff_get_none(): void
    {
        $customer = User::factory()->create();
        $html = $this->actingAs($customer)->get(route('services.index'))->assertOk()->getContent();
        $this->assertNotNull($this->bottomNav($html, 'data-customer-bottom'));
        $this->assertStringNotContainsString('data-guest-bottom', $html);
        $this->assertMatchesRegularExpression('/<body class="[^"]*m-has-bottom-nav/', $html);

        $specialist = Specialist::factory()->create();
        $staff = $this->actingAs(User::find($specialist->user_id))->get(route('services.index'))->assertOk()->getContent();
        $this->assertStringNotContainsString('data-guest-bottom', $staff);
        $this->assertStringNotContainsString('data-customer-bottom', $staff);
        $this->assertDoesNotMatchRegularExpression('/<body class="[^"]*m-has-bottom-nav/', $staff);
    }

    public function test_open_in_app_banner_is_on_the_home_of_every_template_and_above_the_bottom_menu(): void
    {
        foreach (array_keys(config('themes.layouts')) as $layout) {
            $this->useTemplate($layout.':rose');
            $html = $this->get(route('home'))->assertOk()->getContent();
            $this->assertSame(1, substr_count($html, '<div data-open-in-app data-pwa-hide hidden'), $layout);
            $this->assertStringContainsString('intent://salon/rasta#Intent;', $html, $layout);
        }
        $this->useTemplate('');
        $html = $this->get(route('home'))->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, '<div data-open-in-app data-pwa-hide hidden'));
        $this->assertStringContainsString('bottom:calc(var(--m-bottom-nav, 0px) + 1rem)', $html);
        $this->assertStringContainsString('bottom:calc(var(--m-bottom-nav, 0px) + .75rem)', $html, 'بنر آیفون هم بالای منوی پایین');

        $this->get(route('services.index'))->assertOk()->assertDontSee('data-open-in-app', false);
    }

    public function test_back_button_is_on_inner_pages_only(): void
    {
        foreach (['', 'signature:rose', 'editorial:sage', 'showcase:turquoise', 'app:lilac'] as $site) {
            $this->useTemplate($site);
            $this->get(route('home'))->assertOk()->assertDontSee('data-pwa-back aria-label', false);
            $this->get(route('services.index'))->assertOk()
                ->assertSee('data-pwa-back aria-label', false)
                ->assertSee('<a href="'.route('home').'" class="m-pwa-back', false);
        }

        $user = User::factory()->create(['phone' => '09121234567']);
        Specialist::factory()->create(['phone' => '09121234567', 'user_id' => $user->id]);
        $this->actingAs($user)->get(route('specialist.my-dashboard'))->assertOk()->assertDontSee('data-pwa-back aria-label', false);
        $this->actingAs($user)->get(route('specialist.wallet.index'))->assertOk()
            ->assertSee('<a href="'.route('specialist.my-dashboard').'" class="m-pwa-back', false);
    }

    public function test_installed_mode_shortens_the_footer_but_keeps_contact(): void
    {
        $this->salon->forceFill(['phone' => '02100000002'])->save();
        foreach (['', 'signature:rose'] as $site) {
            $this->useTemplate($site);
            $html = $this->get(route('services.index'))->assertOk()->getContent();
            $this->assertStringContainsString('data-salon-app-promo data-pwa-hide', $html, $site);
            $this->assertGreaterThanOrEqual(3, substr_count($html, 'data-pwa-hide'), $site);
            $this->assertStringContainsString('tel:02100000002', $html, $site);
            $this->assertStringContainsString('html.is-standalone [data-pwa-hide]{display:none!important}', $html);
        }
    }

    public function test_offline_page_uses_the_salon_palette_on_its_subdomain(): void
    {
        $mahru = ThemeCatalog::toHex(ThemeCatalog::sitePaletteTokens(null)['dark'])['bg'];
        $this->get('/offline')->assertOk()->assertSee('اتصال به اینترنت برقرار نیست')
            ->assertSee('--bg: '.$mahru, false)->assertSee('mahru-theme', false);

        $this->useTemplate('signature:rose');
        $this->salon->forceFill(['verified_at' => now()])->save();
        $rose = ThemeCatalog::sitePaletteTokens('rose');
        config(['app.central_domain' => 'arisheh.test']);

        $this->get('http://rasta.arisheh.test/offline')->assertOk()
            ->assertSee('--bg: '.ThemeCatalog::toHex($rose['dark'])['bg'], false)
            ->assertSee('--bg: '.ThemeCatalog::toHex($rose['light'])['bg'], false);

        // سالن ناشناخته یا دامنه‌ی اصلی: طلایی ماهرو
        $this->get('http://nobody.arisheh.test/offline')->assertOk()->assertSee('--bg: '.$mahru, false);
        $this->get('http://arisheh.test/offline')->assertOk()->assertSee('--bg: '.$mahru, false);
    }

    public function test_theme_color_follows_the_page_background(): void
    {
        $this->get(route('home'))->assertOk()
            ->assertSee('<meta name="theme-color"', false)
            ->assertSee("meta.setAttribute('content', bg)", false)
            ->assertSee("attributeFilter: ['data-theme', 'class']", false);
    }
}
