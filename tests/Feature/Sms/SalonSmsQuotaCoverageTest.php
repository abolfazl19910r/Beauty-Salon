<?php

namespace Tests\Feature\Sms;

use App\Jobs\CancelUnpaidBookings;
use App\Models\Booking;
use App\Models\Leave;
use App\Models\LoyaltyPoint;
use App\Models\Review;
use App\Models\Reward;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Notifications\Booking\BookingRescheduledNotification;
use App\Notifications\Booking\SpecialistBookingCancelledNotification;
use App\Notifications\Leave\LeaveStatusNotification;
use App\Notifications\Loyalty\PointsEarned;
use App\Notifications\Loyalty\RewardRedeemed;
use App\Notifications\Review\NewReviewNotification;
use App\Notifications\Review\NewReviewReceivedNotification;
use App\Notifications\Withdrawal\Approved\WithdrawalApprovedNotification;
use App\Notifications\Withdrawal\Rejected\WithdrawalRejectedNotification;
use App\Services\SMSService;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * تصمیم ۲۰۲۶-۰۹-۳۰: فقط کد تأیید خرج پلتفرم است؛ هر پیامک دیگری از سهمیه‌ی ماهانه‌ی همان سالن. این ۹ اعلان و لغو خودکار
 * نوبت salon_id به SMSService::send() نمی‌دادند، پس از سهمیه کم نمی‌شدند و با تمام شدن سهمیه هم قطع نمی‌شدند.
 * هر چیز در سالن دوم ساخته می‌شود تا نتیجه از سالن جاری تست نیاید. در همان بررسی چهار ارسال دیگر هم پیدا شد (کد تخفیف ×۲،
 * اعلان پرداخت به مدیر، تغییر زمان نوبت از پنل مشتری) که همین‌جا پوشش داده شده‌اند.
 */
class SalonSmsQuotaCoverageTest extends TestCase
{
    use RefreshDatabase;

    private Salon $salon;

    private array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->salon = Salon::factory()->create(['slug' => 'quota-coverage']);
        $this->mock(SMSService::class, function ($mock) {
            $mock->shouldReceive('send')->andReturnUsing(function ($phone, $message, $salonId = null) {
                $this->sent[] = $salonId;

                return true;
            });
        });
    }

    /** @template T  @param callable(): T $make  @return T */
    private function inSalon(callable $make): mixed
    {
        $previous = app(CurrentSalon::class)->get();
        app(CurrentSalon::class)->set($this->salon);
        try {
            return $make();
        } finally {
            app(CurrentSalon::class)->set($previous);
        }
    }

    private function assertChargedToSalon(object $notification, object $notifiable, string $label): void
    {
        $this->sent = [];
        app(CurrentSalon::class)->clear(); // مثل worker صف sms
        $notification->toSms($notifiable);

        $this->assertSame([$this->salon->id], array_map(fn ($id) => $id === null ? null : (int) $id, $this->sent), "{$label} is not charged to the salon's SMS quota");
    }

    public function test_every_non_verification_sms_is_charged_to_its_salon(): void
    {
        [$booking, $specialist, $customer, $review, $withdrawal, $point, $reward, $leave, $code] = $this->inSalon(function () {
            $specialist = Specialist::factory()->create(['salon_id' => $this->salon->id]);
            $customer = User::factory()->create(['user_type' => 'customer', 'salon_id' => $this->salon->id]);
            $booking = Booking::factory()->create(['specialist_id' => $specialist->id, 'user_id' => $customer->id]);

            return [
                $booking, $specialist, $customer,
                Review::factory()->create(['booking_id' => $booking->id, 'specialist_id' => $specialist->id, 'user_id' => $customer->id]),
                WithdrawalRequest::factory()->create(['specialist_id' => $specialist->id, 'wallet_id' => $specialist->getOrCreateWallet()->id]),
                LoyaltyPoint::factory()->create(['user_id' => $customer->id]),
                Reward::factory()->create(),
                Leave::factory()->create(['specialist_id' => $specialist->id]),
                \App\Models\DiscountCode::factory()->create(),
            ];
        });
        $specialistUser = User::find($specialist->user_id);

        $this->assertChargedToSalon(new SpecialistBookingCancelledNotification($booking, 'customer'), $specialistUser, 'SpecialistBookingCancelledNotification');
        $this->assertChargedToSalon(new BookingRescheduledNotification($booking, now()->subDay()), $customer, 'BookingRescheduledNotification');
        $this->assertChargedToSalon(new NewReviewNotification($booking), $customer, 'NewReviewNotification');
        $this->assertChargedToSalon(new NewReviewReceivedNotification($review), $specialistUser, 'NewReviewReceivedNotification');
        $this->assertChargedToSalon(new WithdrawalApprovedNotification($withdrawal), $specialistUser, 'WithdrawalApprovedNotification');
        $this->assertChargedToSalon(new WithdrawalRejectedNotification($withdrawal, 'شبا نامعتبر'), $specialistUser, 'WithdrawalRejectedNotification');
        $this->assertChargedToSalon(new PointsEarned($point), $customer, 'PointsEarned');
        $this->assertChargedToSalon(new RewardRedeemed($reward, $code), $customer, 'RewardRedeemed');
        $this->assertChargedToSalon(new LeaveStatusNotification($leave), $specialistUser, 'LeaveStatusNotification');
        $this->assertChargedToSalon(new \App\Notifications\Admin\Payment\AdminPaymentReceivedNotification($booking), $specialistUser, 'AdminPaymentReceivedNotification');
    }

    public function test_discount_code_sms_is_charged_to_the_codes_salon(): void
    {
        $customer = $this->inSalon(fn () => User::factory()->create(['user_type' => 'customer', 'salon_id' => $this->salon->id]));
        $this->sent = [];

        $code = $this->inSalon(fn () => \App\Models\DiscountCode::factory()->create(['user_id' => $customer->id, 'max_uses' => 1, 'used_count' => 0, 'is_active' => true]));
        $code->update(['used_count' => 1]); // سقف استفاده → پیامک «منقضی شد»

        $this->assertCount(2, $this->sent);
        $this->assertSame([$this->salon->id, $this->salon->id], array_map('intval', $this->sent));
    }

    public function test_the_customers_reschedule_sms_is_charged_to_the_bookings_salon(): void
    {
        $salonId = app(CurrentSalon::class)->id();
        $customer = User::factory()->create(['user_type' => 'customer', 'salon_id' => $salonId]);
        $service = \App\Models\BeautyService::factory()->create(['duration' => 30]);
        $target = now()->addDays(3)->setTime(11, 0);
        $specialist = Specialist::factory()->create(['auto_confirm_bookings' => true]);
        \App\Models\SpecialistSchedule::factory()->create([
            'specialist_id' => $specialist->id, 'day_of_week' => $target->dayOfWeek,
            'start_time' => '08:00', 'end_time' => '20:00', 'is_active' => true,
        ]);
        $booking = Booking::factory()->create([
            'user_id' => $customer->id, 'specialist_id' => $specialist->id, 'service_id' => $service->id,
            'status' => 'confirmed', 'booking_time' => now()->addDays(5),
        ]);
        $this->sent = [];

        $this->actingAs($customer)->putJson(route('bookings.update-reschedule', ['booking' => $booking->id]), [
            'booking_time' => $target->format('Y-m-d H:i:s'),
        ])->assertOk();

        $this->assertNotEmpty($this->sent);
        $this->assertNotContains(null, $this->sent, 'an SMS of the reschedule is not charged to the salon');
        $this->assertSame([$booking->salon_id], array_values(array_unique(array_map('intval', $this->sent))));
    }

    public function test_the_automatic_unpaid_cancellation_sms_is_charged_to_the_bookings_salon(): void
    {
        $booking = $this->inSalon(fn () => Booking::factory()->create([
            'specialist_id' => Specialist::factory()->create(['salon_id' => $this->salon->id])->id,
            'user_id' => User::factory()->create(['user_type' => 'customer', 'salon_id' => $this->salon->id])->id,
            'status' => 'pending_payment', 'payment_status' => 'unpaid',
        ]));
        Booking::withoutGlobalScopes()->whereKey($booking->id)->update(['created_at' => now()->subHour()]);
        app(CurrentSalon::class)->clear();

        app()->call([new CancelUnpaidBookings, 'handle']);

        $this->assertSame('cancelled', Booking::withoutGlobalScopes()->find($booking->id)->status);
        $this->assertContains($this->salon->id, array_map('intval', array_filter($this->sent)), 'the cancellation SMS is not charged to the salon');
        $this->assertNotContains(null, $this->sent);
    }
}
