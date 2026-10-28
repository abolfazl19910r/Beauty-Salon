<?php

namespace Tests\Feature\Booking;

use App\Jobs\CancelUnpaidBookings;
use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Specialist;
use App\Models\SpecialistSchedule;
use App\Models\User;
use App\Models\WalletSetting;
use App\Services\Booking\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * دو باگ پیش‌پرداخت (بسته‌ی 5ه، ۲۰۲۶-۱۰-۲۸):
 * ۱. پیش‌پرداخت صفر + متخصص بدون تأیید خودکار ← نوبت pending_payment ساخته و بعد از ۳۰ دقیقه با پیامک «پرداخت نکردید» لغو می‌شد.
 * ۲. پیش‌پرداخت + متخصص با تأیید خودکار ← نوبت مستقیم confirmed و unpaid؛ لغو خودکار نمی‌دیدش و ساعت متخصص بی‌پرداخت برای همیشه نگه
 *    داشته می‌شد (و یادآوری هم می‌رفت).
 */
class PrepaymentLifecycleBugsTest extends TestCase
{
    use RefreshDatabase;

    private function specialist(bool $autoConfirm): Specialist
    {
        $specialist = $autoConfirm
            ? Specialist::factory()->autoConfirm()->create()
            : Specialist::factory()->manualConfirm()->create();

        for ($day = 0; $day <= 6; $day++) {
            SpecialistSchedule::factory()->create([
                'specialist_id' => $specialist->id, 'day_of_week' => $day,
                'start_time' => '08:00', 'end_time' => '20:00', 'is_active' => true,
            ]);
        }

        return $specialist;
    }

    private function book(Specialist $specialist, string $time): Booking
    {
        return app(BookingService::class)->createBooking(
            User::factory()->create()->id,
            BeautyService::factory()->create(['price' => 200000])->id,
            $specialist->id,
            $time,
        );
    }

    private function runCancelUnpaid(): void
    {
        app()->call([new CancelUnpaidBookings, 'handle']);
    }

    public function test_bug1_zero_prepayment_booking_is_never_auto_cancelled_as_unpaid(): void
    {
        WalletSetting::get()->update(['prepayment_percentage' => 0, 'minimum_prepayment_amount' => 0]);

        $booking = $this->book($this->specialist(false), now()->addDays(3)->setTime(10, 0)->format('Y-m-d H:i:s'));

        $this->assertSame('pending', $booking->status, 'بدون مبلغی برای پرداخت، نوبت منتظر تأیید سالن است نه منتظر پرداخت');

        $this->travel(31)->minutes();
        $this->runCancelUnpaid();

        $this->assertSame('pending', $booking->fresh()->status);
        $this->assertNull($booking->fresh()->cancelled_at);
    }

    public function test_bug2_auto_confirm_specialist_does_not_hold_an_unpaid_slot_forever(): void
    {
        WalletSetting::get()->update(['prepayment_percentage' => 30, 'minimum_prepayment_amount' => 50000]);

        $booking = $this->book($this->specialist(true), now()->addDays(1)->setTime(10, 0)->format('Y-m-d H:i:s'));

        $this->assertSame('pending_payment', $booking->status, 'نوبتی که پیش‌پرداخت دارد پیش از پرداخت تأییدشده نیست');
        $this->assertSame('unpaid', $booking->payment_status);

        $this->travel(31)->minutes();
        $this->runCancelUnpaid();

        $this->assertSame('cancelled', $booking->fresh()->status, 'ساعت متخصص بعد از مهلت پرداخت آزاد می‌شود');
    }
}
