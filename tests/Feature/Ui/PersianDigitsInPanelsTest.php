<?php

namespace Tests\Feature\Ui;

use App\Models\Booking;
use App\Models\Specialist;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * بسته‌ی ۰ (۲۰۲۶-۱۱-۲۴): رقم‌های متن دیدنی پنل‌ها فارسی‌اند (پیش‌تر تاریخ `1405/09/06`، موبایل، «34 نوبت»، `10.00%` و
 * `7,807,200` لاتین بودند). فقط متن گره‌ها بررسی می‌شود، نه script/style و ویژگی‌هایی مثل value و href.
 */
class PersianDigitsInPanelsTest extends TestCase
{
    use RefreshDatabase;

    /** متن‌هایی که عمداً لاتین می‌مانند (نسخه‌ی اپ، کد درگاه و…) */
    private const ALLOWED = ['/https?:\/\/\S+/u', '/\b\d{1,3}(\.\d{1,3}){3}\b/u', '/\bv?\d+\.\d+\.\d+\b/u'];

    private function latinDigitsIn(string $html): array
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        $found = [];
        foreach ((new DOMXPath($dom))->query('//body//text()[not(ancestor::script) and not(ancestor::style) and not(ancestor::*[@dir="ltr"][contains(@class,"font-mono")])]') as $node) {
            $text = trim(preg_replace('/\s+/u', ' ', $node->textContent));
            foreach (self::ALLOWED as $pattern) {
                $text = preg_replace($pattern, '', $text);
            }
            if (preg_match('/\d/', $text)) {
                $found[] = $text.(getenv('FA_DEBUG') ? ' @ '.$dom->saveHTML($node->parentNode) : '');
            }
        }

        return array_values(array_unique($found));
    }

    public function test_admin_bookings_and_dashboard_show_persian_digits(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        foreach ([1, 2, 3] as $i) {
            Booking::factory()->create(['status' => 'confirmed', 'booking_time' => now()->addDays($i)->setTime(14, 30)]);
        }

        foreach (['admin.bookings.index', 'admin.dashboard'] as $route) {
            $html = $this->actingAs($admin)->get(route($route))->assertOk()->getContent();
            $this->assertSame([], $this->latinDigitsIn($html), $route);
        }
    }

    public function test_specialist_panel_shows_persian_digits(): void
    {
        $user = User::factory()->create(['phone' => '09121234567']);
        $specialist = Specialist::factory()->create(['phone' => '09121234567', 'user_id' => $user->id]);
        foreach ([3, 5] as $h) {
            Booking::factory()->create(['specialist_id' => $specialist->id, 'status' => 'confirmed', 'booking_time' => now()->addHours($h)]);
        }

        foreach (['specialist.my-dashboard', 'specialist.bookings.index'] as $route) {
            $html = $this->actingAs($user)->get(route($route))->assertOk()->getContent();
            $this->assertSame([], $this->latinDigitsIn($html), $route);
        }
    }

    public function test_customer_panel_shows_persian_digits(): void
    {
        $customer = User::factory()->create();
        foreach ([2, 4] as $d) {
            Booking::factory()->create(['user_id' => $customer->id, 'status' => 'confirmed', 'booking_time' => now()->addDays($d)]);
        }

        foreach (['dashboard', 'bookings.index'] as $route) {
            $html = $this->actingAs($customer)->get(route($route))->assertOk()->getContent();
            $this->assertSame([], $this->latinDigitsIn($html), $route);
        }
    }
}
