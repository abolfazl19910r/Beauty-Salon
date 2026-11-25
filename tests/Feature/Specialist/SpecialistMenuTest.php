<?php

namespace Tests\Feature\Specialist;

use App\Models\Booking;
use App\Models\Specialist;
use App\Models\User;
use App\Support\Specialist\SpecialistMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * منوی پنل کارشناس (بسته‌ی ۳ ظاهری، ۲۰۲۶-۱۱-۲۵): یک منبع برای منوی کناری و منوی پایین موبایل، ترتیب پیش‌نمایش،
 * «پروفایل و QR» یک آیتم، و نشان شمار «نوبت‌ها» = نوبت‌های آینده‌ی منتظر تأیید همین کارشناس.
 */
class SpecialistMenuTest extends TestCase
{
    use RefreshDatabase;

    private function specialistWithBookings(): Specialist
    {
        $specialist = Specialist::factory()->create();
        $make = fn (string $status, int $days, int $hour = 12) => Booking::factory()->create([
            'specialist_id' => $specialist->id,
            'status' => $status,
            'booking_time' => now()->addDays($days)->setTime($hour, 0),
        ]);

        $make('pending', 1);
        $make('pending', 3);
        $make('pending', -2);          // گذشته: شمرده نمی‌شود
        $make('confirmed', 1, 14);
        $make('pending_payment', 1, 16);   // کارشناس کاری رویش ندارد
        Booking::factory()->create(['specialist_id' => Specialist::factory()->create()->id, 'status' => 'pending', 'booking_time' => now()->addDay()]); // کارشناس دیگر

        return $specialist;
    }

    public function test_sidebar_follows_the_approved_order_with_the_waiting_badge(): void
    {
        $specialist = $this->specialistWithBookings();

        $html = $this->actingAs(User::find($specialist->user_id))
            ->get(route('specialist.my-dashboard'))
            ->assertOk()
            ->assertSee('data-spec-salon', false)
            ->assertSee('رزرو نوبت برای خودم')
            ->assertSee('صفحه‌ی سالن')
            ->getContent();

        preg_match_all('/data-spec-nav-item="([a-z]+)"/', $html, $m);
        $this->assertSame(array_keys(SpecialistMenu::ITEMS), $m[1]);
        $this->assertSame(['dashboard', 'bookings', 'schedule', 'leaves', 'wallet', 'reviews', 'reports', 'profile'], $m[1]);

        $this->assertMatchesRegularExpression('/data-spec-nav-item="bookings"[^>]*>.*?data-spec-badge[^>]*>۲</s', $html);
        $this->assertStringNotContainsString('QR من', $html);
        $this->assertStringContainsString('پروفایل و QR', $html);
    }

    public function test_mobile_bottom_menu_is_home_bookings_wallet_profile(): void
    {
        $specialist = $this->specialistWithBookings();

        $html = $this->actingAs(User::find($specialist->user_id))
            ->get(route('specialist.bookings.index'))
            ->assertOk()
            ->getContent();

        $this->assertSame(1, preg_match('/<nav[^>]*data-spec-bottom[^>]*>(.*?)<\/nav>/s', $html, $bottom));
        foreach (['خانه', 'نوبت‌ها', 'کیف پول', 'پروفایل'] as $label) {
            $this->assertStringContainsString($label, $bottom[1]);
        }
        $this->assertStringNotContainsString('تقویم', $bottom[1]);
        $this->assertStringNotContainsString('آمار', $bottom[1]);
        $this->assertStringContainsString('href="'.route('specialist.wallet.index').'"', $bottom[1]);
        $this->assertMatchesRegularExpression('/aria-current="page"[^>]*>\s*<svg.*?<\/svg>\s*نوبت‌ها/s', $bottom[1]);
    }

    public function test_qr_page_highlights_the_profile_item(): void
    {
        $specialist = Specialist::factory()->create();

        $html = $this->actingAs(User::find($specialist->user_id))->get(route('specialist.qr'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/data-spec-nav-item="profile"\s+aria-current="page"/', $html);
    }

    public function test_no_badge_without_waiting_bookings(): void
    {
        $specialist = Specialist::factory()->create();

        $this->actingAs(User::find($specialist->user_id))
            ->get(route('specialist.my-dashboard'))
            ->assertOk()
            ->assertDontSee('data-spec-badge', false);
    }
}
