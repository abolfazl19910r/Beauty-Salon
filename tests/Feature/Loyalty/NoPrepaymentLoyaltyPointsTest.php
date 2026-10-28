<?php

namespace Tests\Feature\Loyalty;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\LoyaltyPoint;
use App\Models\LoyaltySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * بسته‌ی 5ه، تصمیم ۹: امتیاز نوبت «بدون پیش‌پرداخت» فقط با کلید مدیر و دو عددی که خودش وارد کرده؛ فقط «انجام‌شده».
 */
class NoPrepaymentLoyaltyPointsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->owner = User::factory()->create(['is_admin' => true]);
    }

    private function booking(array $extra = []): Booking
    {
        return Booking::factory()->withoutPrepayment()->create(array_merge([
            'service_id' => BeautyService::factory()->create(['price' => 250000])->id,
            'user_id' => User::factory()->create(['user_type' => 'customer'])->id,
            'status' => 'confirmed', 'booking_time' => now()->addDay(),
        ], $extra));
    }

    private function points(Booking $booking): int
    {
        return (int) LoyaltyPoint::where('booking_id', $booking->id)->where('type', 'earned')->sum('points');
    }

    public function test_no_points_while_the_switch_is_off(): void
    {
        $booking = $this->booking();
        $booking->update(['status' => 'completed']);

        $this->assertSame(0, $this->points($booking));
    }

    public function test_turning_it_on_needs_both_numbers_from_the_admin(): void
    {
        $this->actingAs($this->owner)->put(route('admin.booking-policy.loyalty'), ['no_prepay_points_enabled' => '1'])
            ->assertSessionHasErrors(['no_prepay_fixed_points', 'no_prepay_amount_per_point']);

        $this->assertNull(LoyaltySetting::noPrepaymentPoints());
        $this->assertNull(LoyaltySetting::getValue(LoyaltySetting::NO_PREPAY_ENABLED));
    }

    public function test_a_completed_no_prepayment_booking_earns_the_admins_points_once(): void
    {
        $this->actingAs($this->owner)->put(route('admin.booking-policy.loyalty'), [
            'no_prepay_points_enabled' => '1', 'no_prepay_fixed_points' => 4, 'no_prepay_amount_per_point' => 100000,
        ])->assertRedirect(route('admin.booking-policy.edit'));

        $booking = $this->booking();
        $booking->update(['status' => 'completed']);
        $this->assertSame(6, $this->points($booking), '۴ ثابت + floor(۲۵۰٬۰۰۰ / ۱۰۰٬۰۰۰)');

        $booking->update(['status' => 'confirmed']);
        $booking->update(['status' => 'completed']);
        $this->assertSame(6, $this->points($booking), 'یک بار برای هر نوبت');
    }

    public function test_no_show_and_prepaid_bookings_get_nothing_from_this_rule(): void
    {
        LoyaltySetting::setValue(LoyaltySetting::NO_PREPAY_ENABLED, '1');
        LoyaltySetting::setValue(LoyaltySetting::NO_PREPAY_FIXED, 4);
        LoyaltySetting::setValue(LoyaltySetting::NO_PREPAY_PER_AMOUNT, 100000);

        $noShow = $this->booking();
        $noShow->update(['status' => 'no_show']);
        $this->assertSame(0, $this->points($noShow));

        $prepaid = $this->booking(['prepayment_required' => true, 'prepayment_amount' => 75000, 'booking_time' => now()->addDays(2)]);
        $prepaid->update(['status' => 'completed']);
        $this->assertSame(0, $this->points($prepaid), 'نوبت با پیش‌پرداخت امتیازش را هنگام پرداخت می‌گیرد');
    }

    public function test_the_booking_mode_page_shows_the_switch(): void
    {
        $this->actingAs($this->owner)->get(route('admin.booking-policy.edit'))->assertOk()
            ->assertSee('امتیاز برای نوبت‌های بدون پیش‌پرداخت')
            ->assertSee('هر چند تومان از قیمت خدمت = ۱ امتیاز');
    }
}
