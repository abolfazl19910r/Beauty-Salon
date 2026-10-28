<?php

namespace Tests\Feature\Booking;

use App\Models\Booking;
use App\Models\BookingPolicy;
use App\Models\User;
use App\Notifications\Booking\BookingModeFallbackNotification;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * بسته‌ی 5ه، تصمیم ۲: «با پیش‌پرداخت» بی درگاه فعال یا بی امکان پلن ← خودکار «بدون پیش‌پرداخت» + هشدار به مالک؛ رزرو قطع نمی‌شود.
 */
class BookingModeFallbackTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->owner = User::factory()->create(['is_admin' => true]);
        $this->usePrepaymentMode();
    }

    public function test_deactivating_the_last_gateway_falls_back_and_warns_the_owner(): void
    {
        $waiting = Booking::factory()->create(['status' => 'pending_payment', 'payment_status' => 'unpaid', 'booking_time' => now()->addDays(2)]);

        foreach (app(CurrentSalon::class)->get()->paymentGateways as $gateway) {
            $gateway->update(['is_active' => false]);
        }

        $policy = BookingPolicy::current();
        $this->assertSame(BookingPolicy::MODE_NONE, $policy->payment_mode);
        $this->assertNotNull($policy->payment_mode_fallback_at);
        Notification::assertSentTo($this->owner, BookingModeFallbackNotification::class,
            fn ($n) => str_contains($n->message(), 'درگاه') && $n->waivedBookings === 1);

        $this->assertSame('pending', $waiting->fresh()->status, 'نوبت در انتظار پرداخت منتظر تأیید شد، لغو نشد');
        $this->assertFalse($waiting->fresh()->prepayment_required);

        $this->actingAs($this->owner)->get(route('admin.booking-policy.edit'))->assertOk()->assertSee('خودکار متوقف شد');
    }

    public function test_a_salon_that_still_has_an_active_gateway_is_left_alone(): void
    {
        $salon = app(CurrentSalon::class)->get();
        $salon->paymentGateways()->create(['driver' => 'zibal', 'credentials' => ['merchant' => 'zibal-merchant-1'], 'is_active' => true, 'priority' => 2]);
        $salon->paymentGateways()->orderBy('priority')->first()->update(['is_active' => false]);

        $this->assertSame(BookingPolicy::MODE_PREPAY, BookingPolicy::current()->payment_mode);
        Notification::assertNothingSent();
    }

    public function test_the_scheduled_check_catches_a_gateway_lost_without_the_observer(): void
    {
        // تغییر مستقیم در دیتابیس (یا از دست رفتن امکان پلن) — observer نمی‌بیند؛ فرمان زمان‌بندی‌شده می‌بیند
        app(CurrentSalon::class)->get()->paymentGateways()->toBase()->update(['is_active' => false]);

        $this->artisan('bookings:enforce-payment-mode')->assertSuccessful();

        $this->assertSame(BookingPolicy::MODE_NONE, BookingPolicy::current()->payment_mode);
        Notification::assertSentTo($this->owner, BookingModeFallbackNotification::class);

        // اجرای دوباره چیزی نمی‌فرستد
        Notification::fake();
        $this->artisan('bookings:enforce-payment-mode')->assertSuccessful();
        Notification::assertNothingSent();
    }
}
