<?php

namespace Tests\Feature\Factories;

use App\Models\Booking;
use App\Models\WalletSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ ریشه‌ی «تست ناپایدار» چند نشست (۲۰۲۶-۰۹-۲۶): BookingFactory تاریخ رو از «+۱ روز» می‌کشید و بعد ساعت رو تصادفی بین
 * ۹ تا ۱۷ می‌گذاشت. اگر تاریخ فردا می‌افتاد و ساعت کشیده‌شده زودتر از ساعت فعلی بود، نوبت کمتر از ۲۴ ساعت بعد بود — داخل
 * بازه‌ی جریمه‌ی لغو (cancellation_before_hours = ۲۴، ۲۰٪) — و مثلاً BookingServiceTest بازپرداخت ۴۸٬۰۰۰ به‌جای ۶۰٬۰۰۰ می‌دید.
 * احتمالش به ساعت اجرای سوییت بستگی داشت (شب‌ها بیشتر). حالا از «+۲ روز»: زودترین نوبت پس‌فردا ساعت ۹ است.
 */
class BookingFactoryTimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_bookings_are_always_outside_the_customer_cancellation_fee_window(): void
    {
        // بدترین حالت ساعت اجرا ~۱۷:۳۰: بیشتر «فردا» داخل بازه‌ی قرعه‌ست و هر ساعت ۹ تا ۱۷ زودتر از ساعت فعلیه
        // (~۰٫۴۵٪ هر قرعه با بازه‌ی قدیمی). Faker با seed ثابت → نتیجه تکرارپذیر، نه احتمالاتی.
        $this->travelTo(now()->setTime(17, 30));
        fake()->seed(20260926);
        $window = (int) (WalletSetting::get()->cancellation_before_hours ?: 24);

        $closest = collect(range(1, 1000))
            ->map(fn () => now()->diffInHours(Booking::factory()->make(['status' => 'cancelled'])->booking_time, false))
            ->min();

        $this->assertGreaterThan($window, $closest, 'نوبت factory نباید داخل بازه‌ی جریمه‌ی لغو بیفته');
    }
}
