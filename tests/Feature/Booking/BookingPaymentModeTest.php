<?php

namespace Tests\Feature\Booking;

use App\Jobs\CancelUnpaidBookings;
use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\BookingPolicy;
use App\Models\DiscountCode;
use App\Models\Specialist;
use App\Models\SpecialistSchedule;
use App\Models\User;
use App\Models\WalletSetting;
use App\Notifications\Booking\BookingNotification;
use App\Services\Booking\BookingService;
use App\Services\SMSService;
use App\Support\Booking\PurchaseTerms;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * «نحوه‌ی رزرو» (بسته‌ی 5ه، تصمیم‌های ۱ تا ۶): پیش‌فرض «بدون پیش‌پرداخت»؛ برچسب هر نوبت؛ چرخه، پیامک و تخفیف در هر حالت.
 */
class BookingPaymentModeTest extends TestCase
{
    use RefreshDatabase;

    private function specialist(bool $autoConfirm = false): Specialist
    {
        $specialist = Specialist::factory()->create(['auto_confirm_bookings' => $autoConfirm]);
        for ($day = 0; $day <= 6; $day++) {
            SpecialistSchedule::factory()->create([
                'specialist_id' => $specialist->id, 'day_of_week' => $day,
                'start_time' => '08:00', 'end_time' => '20:00', 'is_active' => true,
            ]);
        }

        return $specialist;
    }

    private function book(Specialist $specialist, ?User $user = null, ?string $code = null, int $price = 200000): Booking
    {
        WalletSetting::get()->update(['prepayment_percentage' => 30, 'minimum_prepayment_amount' => 50000]);

        return app(BookingService::class)->createBooking(
            ($user ?? User::factory()->create())->id,
            BeautyService::factory()->create(['price' => $price])->id,
            $specialist->id,
            now()->addDays(3)->setTime(10, 0)->format('Y-m-d H:i:s'),
            $code,
        );
    }

    public function test_a_new_salon_books_without_prepayment_by_default(): void
    {
        $this->assertSame(BookingPolicy::MODE_NONE, BookingPolicy::current()->payment_mode);

        $booking = $this->book($this->specialist());

        $this->assertSame('pending', $booking->status, 'منتظر تأیید سالن، نه منتظر پرداخت');
        $this->assertFalse($booking->prepayment_required);
        $this->assertSame(0.0, (float) $booking->prepayment_amount);
        $this->assertFalse($booking->isPayable());
        $this->assertSame(200000.0, $booking->remaining_amount, 'کل مبلغ در سالن');
    }

    public function test_auto_confirming_specialist_confirms_a_no_prepayment_booking_at_once(): void
    {
        $this->assertSame('confirmed', $this->book($this->specialist(true))->status);
    }

    public function test_customer_and_specialist_are_told_at_booking_time_without_prepayment(): void
    {
        Notification::fake();
        $sms = [];
        $this->mock(SMSService::class, function ($mock) use (&$sms) {
            $mock->shouldReceive('send')->andReturnUsing(function ($phone, $text) use (&$sms) {
                $sms[] = $text;

                return true;
            });
            $mock->shouldIgnoreMissing(true);
        });

        $specialist = $this->specialist();
        $booking = $this->book($specialist, User::factory()->create(['phone' => '09120000001']));

        Notification::assertSentTo($specialist, BookingNotification::class);
        $this->assertNotEmpty(array_filter($sms, fn ($t) => str_contains($t, 'منتظر تایید') && str_contains($t, number_format(200000))),
            'پیامک ثبت نوبت مشتری با مبلغ کل همان لحظه‌ی ثبت');
        $this->assertSame('unpaid', $booking->fresh()->payment_status);
    }

    public function test_a_discount_comes_off_the_full_price_without_prepayment(): void
    {
        DiscountCode::create(['code' => 'OFF10', 'type' => 'percentage', 'amount' => 10, 'max_uses' => 10, 'used_count' => 0, 'is_active' => true]);

        $booking = $this->book($this->specialist(), null, 'OFF10');

        $this->assertSame(20000.0, (float) $booking->discount_amount, '۱۰٪ از کل ۲۰۰٬۰۰۰، نه از پیش‌پرداخت صفر');
        $this->assertSame(180000.0, $booking->fresh('service')->remaining_amount);
    }

    public function test_unpaid_auto_cancel_never_touches_a_no_prepayment_booking(): void
    {
        $booking = $this->book($this->specialist());
        // حتی اگر وضعیتش به هر دلیلی pending_payment باشد، برچسب ملاک است
        $booking->update(['status' => 'pending_payment']);

        $this->travel(40)->minutes();
        app()->call([new CancelUnpaidBookings, 'handle']);

        $this->assertSame('pending_payment', $booking->fresh()->status);
    }

    public function test_prepayment_mode_labels_the_booking_and_keeps_the_old_rules(): void
    {
        $this->usePrepaymentMode();

        $booking = $this->book($this->specialist(true));

        $this->assertTrue($booking->prepayment_required);
        $this->assertSame('pending_payment', $booking->status);
        $this->assertSame(60000.0, (float) $booking->prepayment_amount);
        $this->assertTrue($booking->isPayable());
    }

    public function test_prepayment_mode_without_an_active_gateway_books_without_prepayment(): void
    {
        $this->usePrepaymentMode();
        app(CurrentSalon::class)->get()->paymentGateways()->update(['is_active' => false]);

        $booking = $this->book($this->specialist());

        $this->assertFalse($booking->prepayment_required, 'نوبتی که پرداختش ممکن نیست هرگز «در انتظار پرداخت» نمی‌شود');
        $this->assertSame('pending', $booking->status);
    }

    public function test_the_booking_label_not_the_current_mode_decides_what_is_payable(): void
    {
        $this->usePrepaymentMode();
        $prepaid = $this->book($this->specialist());

        BookingPolicy::current()->update(['payment_mode' => BookingPolicy::MODE_NONE]);

        $this->assertTrue($prepaid->fresh()->isPayable(), 'برچسب موقع ثبت می‌ماند');
        $manual = Booking::factory()->create(['status' => 'confirmed', 'prepayment_required' => false, 'booking_time' => now()->addDays(4)]);
        $this->assertFalse($manual->isPayable(), 'نوبت دستی کادر برچسب «بدون پیش‌پرداخت» دارد');
    }

    public function test_the_web_flow_without_prepayment_goes_to_the_booking_page_not_the_bank(): void
    {
        $user = User::factory()->create();
        $specialist = $this->specialist();
        $service = BeautyService::factory()->create(['price' => 150000, 'duration' => 30]);
        $time = now()->addDays(2)->setTime(11, 0)->format('Y-m-d H:i:s');

        $html = $this->actingAs($user)->post(route('bookings.confirm'), [
            'service_id' => $service->id, 'specialist_id' => $specialist->id, 'booking_time' => $time,
        ])->assertOk()->getContent();
        $this->assertStringContainsString('ثبت نوبت', $html);
        $this->assertStringNotContainsString('رفتن به درگاه پرداخت', $html);
        $this->assertStringContainsString('شرایط لغو و پرداخت', $html);

        $response = $this->actingAs($user)->post(route('bookings.store'), [
            'service_id' => $service->id, 'specialist_id' => $specialist->id, 'booking_time' => $time,
        ]);

        $booking = Booking::sole();
        $response->assertRedirect(route('bookings.show', $booking));
        $this->actingAs($user)->get(route('bookings.show', $booking))->assertOk()->assertSee('پرداخت در سالن (بدون پیش‌پرداخت)');
    }

    public function test_purchase_terms_follow_the_mode(): void
    {
        $none = implode("\n", PurchaseTerms::cancellation(null, false));
        $this->assertStringContainsString('پیش‌پرداخت آنلاین ندارد', $none);
        $this->assertStringNotContainsString('دقیقه پرداخت نشود', $none);

        $this->assertStringContainsString('دقیقه پرداخت نشود', implode("\n", PurchaseTerms::cancellation(null, true)));
    }
}
