<?php

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * سیستم طراحی لندینگ روی صفحه‌های عمومی و مشتری (ادامه ۷): تم تیره/روشن با کلید «mahru-theme»،
 * دکمه‌ی تغییر تم، فونت روی همین سرور (بدون Google Fonts). بسته‌ی ه (۲۰۲۶-۱۰-۱۵): پنل‌های مدیر و متخصص هم.
 */
class MahruThemeTest extends TestCase
{
    use RefreshDatabase;

    private function assertThemed(string $html): void
    {
        $this->assertStringContainsString("localStorage.getItem('mahru-theme')", $html);
        $this->assertStringContainsString('data-m-theme-toggle', $html);
        $this->assertStringContainsString('fonts/mahru/Vazirmatn-Regular.woff2', $html);
        $this->assertStringContainsString('--rasta-gold:rgb(var(--m-gold))', $html, 'متغیرهای قدیمی به توکن‌ها وصل‌اند');
        $this->assertStringNotContainsString('fonts.googleapis.com', $html);
    }

    public function test_public_salon_pages_and_auth_pages_follow_the_landing_rules(): void
    {
        foreach (['/s/rasta', '/s/rasta/services', '/s/rasta/login', '/s/rasta/register', '/s/rasta/forgot-password', '/salon-signup'] as $url) {
            $this->assertThemed($this->get($url)->assertOk()->getContent());
        }
    }

    public function test_customer_pages_are_themed(): void
    {
        $customer = User::factory()->create();

        foreach (['/s/rasta/bookings', '/s/rasta/wallet', '/s/rasta/loyalty', '/s/rasta/profile'] as $url) {
            $html = $this->actingAs($customer)->get($url)->assertOk()->getContent();
            $this->assertThemed($html);
            // متن روی دکمه‌ی طلایی در هر دو تم تیره می‌ماند؛ هیچ «رنگ پس‌زمینه» به‌عنوان رنگ متن نمانده
            $this->assertDoesNotMatchRegularExpression('/(?<![-\w])color:\s*var\(--rasta-dark\)/', $html);
        }
    }

    /** بسته‌ی ه: پنل مدیر و متخصص همان توکن‌ها، دکمه‌ی تم و فونت محلی؛ متغیرهای قدیمی پنل به توکن‌ها وصل‌اند */
    public function test_admin_and_specialist_panels_follow_the_mahru_theme(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        foreach ([route('admin.dashboard'), route('admin.bookings.index'), route('admin.profile.show')] as $url) {
            $html = $this->actingAs($admin)->get($url)->assertOk()->getContent();
            $this->assertPanelThemed($html);
            $this->assertStringContainsString('--admin-accent:rgb(var(--m-gold))', $html);
        }

        $specialist = \App\Models\Specialist::factory()->create();
        foreach ([route('specialist.my-dashboard'), route('specialist.bookings.index'), route('specialist.profile.show')] as $url) {
            $html = $this->actingAs(User::find($specialist->user_id))->get($url)->assertOk()->getContent();
            $this->assertPanelThemed($html);
            $this->assertStringContainsString('--specialist-plum-mid:rgb(var(--m-gold))', $html);
            $this->assertStringNotContainsString('#1F1424', $html, 'بنفش قدیمی پنل متخصص');
        }
    }

    private function assertPanelThemed(string $html): void
    {
        $this->assertThemed($html);
        $this->assertMatchesRegularExpression('/<body class="[^"]*\bm-panel\b/', $html);
        $this->assertStringNotContainsString('rastikerdar/vazir-font', $html, 'فونت از CDN نمی‌آید');
        // رنگ‌های ثابت قدیمی (خاکستری/سفید پنل مدیر) در style نمانده‌اند
        $this->assertDoesNotMatchRegularExpression('/style="[^"]*#(334155|F8FAFC|E2E8F0|1E293B)\b/i', $html);
    }

    /** پنل ابرمدیر بیرون از بسته‌ی ه است (قالب سفید و آبی جدا، پس از پیش‌نمایش) */
    public function test_the_super_admin_panel_is_not_on_the_salon_theme(): void
    {
        $super = User::factory()->create(['is_admin' => true]);
        $super->roles()->attach(\App\Models\Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']));

        $html = $this->actingAs($super)->get(route('superadmin.plans.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('data-m-theme-toggle', $html);
    }
}
