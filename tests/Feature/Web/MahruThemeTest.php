<?php

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * سیستم طراحی لندینگ روی صفحه‌های عمومی و مشتری (ادامه ۷): تم تیره/روشن با کلید «mahru-theme»،
 * دکمه‌ی تغییر تم، فونت روی همین سرور (بدون Google Fonts) — و پنل‌ها دست نخورده.
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

    public function test_the_admin_panel_keeps_its_own_look(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $html = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertStringNotContainsString('data-m-theme-toggle', $html);
    }
}
