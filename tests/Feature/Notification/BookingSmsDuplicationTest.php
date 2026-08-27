<?php

namespace Tests\Feature\Notification;

use App\Events\Booking\BookingCancelled;
use App\Events\Booking\Completed\BookingCompleted;
use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\NotificationSetting;
use App\Models\Specialist;
use App\Models\User;
use App\Notifications\Booking\BookingStatusUpdated;
use App\Notifications\Booking\CustomerBookingNotification;
use App\Services\SMSService;
use App\Support\Notifications\NotificationEvents;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * پوشش تست برای رفع دو باگ پیامک تکراری (زمان ثبت نوبت و زمان تکمیل نوبت) + یک باگ سومِ کشف‌شده
 * حین همین بررسی (پیامک تکراری زمان لغو نوبت) + سیستم جدید تنظیمات کانال اطلاع‌رسانی که ادمین
 * می‌تواند از پنل تنظیمات هرکدام را دوباره روشن/خاموش کند.
 *
 * ⭐ نکته‌ی فنی مهم: `App\Notifications\Booking\*` (CustomerBookingNotification, BookingNotification,
 * BookingStatusUpdated, ...) در سازنده‌ی خودشان مستقیماً `new SMSService` می‌سازند (نه از طریق
 * container) — این الگوی از قبل در پروژه وجود دارد. `tests/TestCase.php` یک mock سراسری برای
 * `SMSService::class` در container بایند می‌کند تا هیچ تستی واقعاً به Kavenegar وصل نشود؛ این mock
 * فقط جایی اثر دارد که SMSService از طریق container resolve بشه (مثل `BookingObserver`/`ReviewService`
 * که با constructor injection تزریق می‌شن)، نه جاهایی که مستقیم `new SMSService` صدا زده می‌شه. برای
 * همین، شمارش دقیق ارسال واقعی این پیامک‌های خام (raw) با override کردن همان mock سراسری
 * (`$this->mock(SMSService::class, ...)`) انجام می‌شود؛ برای کلاس‌های Notification که `new SMSService`
 * می‌سازند، صحت رفتار از طریق بررسی مستقیم خروجی via() تأیید می‌شود (که خودِ همان چیزی‌ست که تصمیم
 * می‌گیرد آیا toSms() اصلاً توسط SmsChannel صدا زده شود یا نه).
 */
class BookingSmsDuplicationTest extends TestCase
{
    use RefreshDatabase;

    private function makeBooking(array $overrides = []): Booking
    {
        $service = BeautyService::factory()->create(['price' => 250000]);
        $specialist = Specialist::factory()->create(['commission_rate' => 10]);
        $user = User::factory()->create();

        return Booking::factory()->create(array_merge([
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'prepayment_amount' => 75000,
            'booking_time' => now()->addDays(10),
        ], $overrides));
    }

    // ── آیتم ۱: حذف کامل «ثبت نوبت جدید — اطلاع به مشتری» (نه فقط پیش‌فرض خاموش) ────

    public function test_customer_booking_notification_never_sends_sms_and_has_no_settings_toggle(): void
    {
        $booking = $this->makeBooking();

        $via = (new CustomerBookingNotification($booking))->via($booking->user);

        $this->assertSame(['database'], $via);
    }

    public function test_customer_booking_notification_ignores_stray_settings_rows_for_its_old_removed_key(): void
    {
        $booking = $this->makeBooking();

        // Even if a stray row exists in the DB for the old (now-removed-from-the-registry) event key,
        // the notification class itself no longer reads it at all — via() is hardcoded.
        NotificationSetting::create([
            'event_key' => 'booking.created.customer',
            'sms_enabled' => true,
            'database_enabled' => true,
            'telegram_enabled' => false,
        ]);

        $via = (new CustomerBookingNotification($booking))->via($booking->user);

        $this->assertSame(['database'], $via);
    }

    public function test_paying_for_a_booking_sends_exactly_one_raw_sms_to_the_customer_by_default(): void
    {
        // Only the customer's raw SMS (BookingObserver::sendCustomerPendingSMS, container-injected
        // SMSService) is asserted here; the specialist's SMS goes through BookingNotification's own
        // `new SMSService()` instance, which the mock below doesn't intercept.
        $this->mock(SMSService::class, function ($mock) {
            $mock->shouldReceive('send')->once();
        });

        $booking = $this->makeBooking(['status' => 'pending']);
        $booking->update(['payment_status' => 'paid']);
    }

    public function test_admin_can_disable_the_customer_pending_approval_sms(): void
    {
        NotificationSetting::create([
            'event_key' => NotificationEvents::BOOKING_PAID_PENDING_APPROVAL_CUSTOMER,
            'sms_enabled' => false,
            'database_enabled' => true,
            'telegram_enabled' => false,
        ]);

        $this->mock(SMSService::class, function ($mock) {
            $mock->shouldReceive('send')->never();
        });

        $booking = $this->makeBooking(['status' => 'pending']);
        $booking->update(['payment_status' => 'paid']);
    }

    public function test_specialist_still_receives_exactly_one_sms_when_a_booking_is_paid(): void
    {
        Notification::fake();

        $booking = $this->makeBooking(['status' => 'pending']);
        $booking->update(['payment_status' => 'paid']);

        Notification::assertSentToTimes($booking->specialist, \App\Notifications\Booking\BookingNotification::class, 1);
    }

    // ── آیتم ۲: ادغام «تشکر» با «لینک نظرسنجی» — پیام تشکر جداگانه کاملاً حذف شد ──

    public function test_booking_status_updated_completed_never_sends_sms_and_has_no_settings_toggle(): void
    {
        $booking = $this->makeBooking(['status' => 'completed', 'payment_status' => 'paid']);

        $via = (new BookingStatusUpdated($booking, 'completed'))->via($booking->user);

        $this->assertSame(['database'], $via);
    }

    public function test_booking_status_updated_confirmed_still_sends_sms_by_default(): void
    {
        $booking = $this->makeBooking(['status' => 'confirmed', 'payment_status' => 'paid']);

        $via = (new BookingStatusUpdated($booking, 'confirmed'))->via($booking->user);

        $this->assertContains('sms', $via);
        $this->assertContains('database', $via);
    }

    public function test_booking_status_updated_completed_ignores_stray_settings_rows_for_its_old_removed_key(): void
    {
        $booking = $this->makeBooking(['status' => 'completed', 'payment_status' => 'paid']);

        NotificationSetting::create([
            'event_key' => 'booking.completed.customer',
            'sms_enabled' => true,
            'database_enabled' => true,
            'telegram_enabled' => false,
        ]);

        $via = (new BookingStatusUpdated($booking, 'completed'))->via($booking->user);

        $this->assertSame(['database'], $via);
    }

    public function test_completing_a_booking_sends_exactly_one_raw_review_request_sms_by_default(): void
    {
        // ReviewService::sendReviewRequest() also uses the container-injected (mocked) SMSService.
        // The BookingStatusUpdated('completed') thank-you SMS uses its own `new SMSService()` and is
        // structurally excluded from 'sms' by default (already proven above at the via() level), so
        // it must not add to this count.
        $this->mock(SMSService::class, function ($mock) {
            $mock->shouldReceive('send')->once();
        });

        $booking = $this->makeBooking([
            'status' => 'completed',
            'payment_status' => 'paid',
            'review_sent_at' => null,
        ]);

        event(new BookingCompleted($booking));
    }

    public function test_completing_an_already_reviewed_booking_does_not_resend_the_review_request(): void
    {
        $this->mock(SMSService::class, function ($mock) {
            $mock->shouldReceive('send')->never();
        });

        $booking = $this->makeBooking([
            'status' => 'completed',
            'payment_status' => 'paid',
            'review_sent_at' => now(),
        ]);

        event(new BookingCompleted($booking));
    }

    // ── کشف جانبی: پیامک تکراری زمان لغو نوبت ────────────────────────────

    public function test_booking_status_updated_cancelled_never_sends_sms_even_if_admin_tries_to_enable_it(): void
    {
        $booking = $this->makeBooking(['status' => 'cancelled', 'payment_status' => 'paid']);

        // The customer-cancellation SMS is exclusively owned by
        // BookingObserver::sendCustomerCancellationSMS(); this notification's own 'sms' branch for
        // 'cancelled' is structurally disabled (not settings-gated) so it can never reintroduce the
        // duplicate that used to exist, even if a stray setting row exists for the cancelled-related
        // event key.
        NotificationSetting::create([
            'event_key' => NotificationEvents::BOOKING_CANCELLED_CUSTOMER,
            'sms_enabled' => true,
            'database_enabled' => true,
            'telegram_enabled' => false,
        ]);

        $via = (new BookingStatusUpdated($booking, 'cancelled'))->via($booking->user);

        $this->assertSame(['database'], $via);
    }

    public function test_cancelling_a_paid_booking_sends_exactly_two_raw_sms_customer_plus_specialist_not_three(): void
    {
        // Mirrors BookingService::cancelBooking() exactly: update() then dispatch the event in the
        // same call. The observer's updated() hook fires sendCancellationSMS() (1 raw SMS to the
        // customer + 1 raw SMS to the specialist, both via the container-injected/mocked
        // SMSService — exactly the 2 calls asserted below). The event's listener additionally calls
        // ->notify(new BookingStatusUpdated($booking,'cancelled')); before the fix, that notification
        // would have added a *third* SMS via its own `new SMSService()`. Per the via()-level test
        // above, its via() now excludes 'sms' entirely for 'cancelled', so Laravel's SmsChannel never
        // even invokes toSms() — meaning no third SMSService instance is created at all for this call,
        // and the total stays at exactly 2.
        $booking = $this->makeBooking(['status' => 'pending', 'payment_status' => 'unpaid']);
        $booking->update(['payment_status' => 'paid']); // establishes income/commission first (1 raw customer SMS, not part of this assertion)

        $this->mock(SMSService::class, function ($mock) {
            $mock->shouldReceive('send')->twice();
        });

        $booking->update(['status' => 'cancelled', 'cancelled_by' => 'customer', 'cancelled_at' => now()]);
        event(new BookingCancelled($booking, 'customer'));
    }

    // ── سیستم تنظیمات: کانال database هم قابل خاموش‌کردن است ────────────

    public function test_disabling_database_channel_prevents_the_in_app_notification_from_being_created(): void
    {
        NotificationSetting::create([
            'event_key' => NotificationEvents::BOOKING_CREATED_SPECIALIST,
            'sms_enabled' => true,
            'database_enabled' => false,
            'telegram_enabled' => false,
        ]);

        $booking = $this->makeBooking(['status' => 'pending']);

        Notification::fake();
        $booking->update(['payment_status' => 'paid']);

        Notification::assertSentTo(
            $booking->specialist,
            \App\Notifications\Booking\BookingNotification::class,
            fn ($notification, $channels) => ! in_array('database', $channels, true)
        );
    }
}