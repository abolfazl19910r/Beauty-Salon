<?php

namespace Tests\Feature\Web;

use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Support\CentralUrl;
use App\Support\CurrentSalon;
use App\Support\CustomerAppLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ۲۰۲۶-۱۱-۰۶ (ابوالفضل): اپ‌ها فقط از سایت خود ماهرو دانلود می‌شوند (نه App Store/Google Play)؛ ولی کسی نمی‌فهمید اپ داریم —
 * مشتری روی /s/rasta (بیرون از اندروید هیچ نشانی نبود)، متخصص در پنلش، صاحب سالن در لندینگ و پنل مدیر، کادر در صفحه‌ی ورود.
 */
class AppDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    private Salon $salon;

    protected function setUp(): void
    {
        parent::setUp();
        $this->salon = app(CurrentSalon::class)->get();
    }

    public function test_landing_has_an_apps_section_footer_link_and_an_honest_faq(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('id="apps"', false)
            ->assertSee('data-landing-apps-customer', false)
            ->assertSee('data-landing-apps-staff', false)
            ->assertSee('ماهرو همکار')
            ->assertSee('href="'.CentralUrl::route('app-download.show').'"', false)
            ->assertSee('data-landing-footer-app', false)
            ->assertSee('اپ اندروید هم هست');
    }

    public function test_staff_login_page_points_specialists_to_the_staff_app(): void
    {
        $this->get(route('login'))->assertOk()
            ->assertSee('data-staff-app-link', false)
            ->assertSee(CentralUrl::route('app-download.show').'#staff', false);

        $this->get(CentralUrl::route('app-download.show'))->assertOk()->assertSee('id="staff"', false);
    }

    public function test_every_salon_page_footer_offers_the_salon_app_on_every_device(): void
    {
        $install = CustomerAppLink::forSalon($this->salon)['install_url'];

        // بدون User-Agent اندروید: پیش‌تر فقط بنر شناور اندروید بود
        foreach (['/s/rasta', '/s/rasta/services'] as $page) {
            $this->get($page)->assertOk()
                ->assertSee('data-salon-app-promo', false)
                ->assertSee('href="'.$install.'"', false);
        }
    }

    public function test_specialist_dashboard_and_menu_offer_the_staff_app(): void
    {
        $user = User::factory()->create(['phone' => '09121234567']);
        Specialist::factory()->create(['phone' => '09121234567', 'user_id' => $user->id]);

        $this->actingAs($user)->get(route('specialist.my-dashboard'))->assertOk()
            ->assertSee('data-staff-app-promo', false)
            ->assertSee('data-staff-app-menu', false)
            ->assertSee('ماهرو همکار');

        $this->get(route('specialist.install'))->assertOk()
            ->assertSee('data-specialist-install-desktop', false);
    }

    public function test_admin_qr_page_explains_both_apps_with_links_to_share(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get(route('admin.salon-qr.index'))->assertOk()
            ->assertSee('data-admin-apps-card', false)
            ->assertSee(CustomerAppLink::forSalon($this->salon)['install_url'], false)
            ->assertSee(CentralUrl::route('app-download.show').'#staff', false);
    }
}
