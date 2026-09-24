<?php

namespace Tests\Feature\Jobs;

use App\Jobs\CancelUnpaidBookings;
use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\PaymentTransaction;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ لغو خودکار نوبت‌های پرداخت‌نشده (هر ۵ دقیقه، routes/console.php) نباید نوبتی رو که مشتری‌اش همین الان در
 * صفحه‌ی بانکه لغو کنه. قبلاً مهلت ۳۰ دقیقه‌ای از ساخت نوبت شمرده می‌شد و پرداخت دیرهنگام یا نوبت لغوشده رو
 * بی‌صدا زنده می‌کرد، یا (وقتی ساعتش رو کس دیگه‌ای گرفته بود) پول مشتری رو بدون نوبت نگه می‌داشت.
 */
class CancelUnpaidBookingsInFlightPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function unpaidBooking(): Booking
    {
        return Booking::factory()->create([
            'user_id' => User::factory()->create()->id,
            'service_id' => BeautyService::factory()->create()->id,
            'specialist_id' => Specialist::factory()->create()->id,
            'status' => 'pending_payment',
            'payment_status' => 'unpaid',
            'prepayment_amount' => 60000,
        ]);
    }

    private function startPayment(Booking $booking, string $status = 'pending'): PaymentTransaction
    {
        return PaymentTransaction::create([
            'salon_id' => $booking->salon_id, 'driver' => 'saman', 'purpose' => 'booking',
            'payable_type' => $booking->getMorphClass(), 'payable_id' => $booking->id,
            'amount_rial' => 600000, 'status' => $status, 'callback_url' => 'https://salon.test/cb',
        ]);
    }

    private function runJob(): void
    {
        app()->call([new CancelUnpaidBookings, 'handle']);
    }

    public function test_a_booking_whose_customer_is_at_the_bank_is_not_cancelled(): void
    {
        $atBank = $this->unpaidBooking();
        $abandoned = $this->unpaidBooking();
        $failedPayment = $this->unpaidBooking();
        $this->travel(28)->minutes();
        $this->startPayment($atBank);                 // دقیقه‌ی ۲۸ رفت به بانک
        $this->startPayment($failedPayment, 'failed'); // تلاش ناموفق = در جریان نیست

        $this->travel(4)->minutes();                  // دقیقه‌ی ۳۲
        $this->runJob();

        $this->assertSame('pending_payment', $atBank->fresh()->status);
        $this->assertSame('cancelled', $abandoned->fresh()->status);
        $this->assertSame('cancelled', $failedPayment->fresh()->status);
    }

    public function test_after_the_transaction_lifetime_the_booking_is_cancelled_as_before(): void
    {
        $booking = $this->unpaidBooking();
        $this->startPayment($booking);

        $this->travel(PaymentTransaction::PENDING_LIFETIME_MINUTES + 1)->minutes();
        $this->runJob();

        $this->assertSame('cancelled', $booking->fresh()->status);
    }

    public function test_the_manual_cleanup_command_skips_in_flight_payments_too(): void
    {
        $atBank = $this->unpaidBooking();
        $abandoned = $this->unpaidBooking();
        $this->travel(31)->minutes();
        $this->startPayment($atBank);

        $this->artisan('bookings:cleanup')->expectsConfirmation('آیا مطمئن هستید که می‌خواهید این نوبت‌ها را لغو کنید؟', 'yes')->assertSuccessful();

        $this->assertSame('pending_payment', $atBank->fresh()->status);
        $this->assertSame('cancelled', $abandoned->fresh()->status);
    }
}
