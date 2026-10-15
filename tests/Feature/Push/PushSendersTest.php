<?php

namespace Tests\Feature\Push;

use App\Jobs\SendBookingReminderJob;
use App\Models\Booking;
use App\Models\DiscountCode;
use App\Models\NotificationSetting;
use App\Models\PushDevice;
use App\Models\PushMessage;
use App\Models\Specialist;
use App\Models\User;
use App\Models\UserNotification;
use App\Notifications\Booking\BookingNotification;
use App\Services\Notification\NotificationSettingService;
use App\Services\Push\PushMode;
use App\Services\Push\PushPolicy;
use App\Support\Notifications\NotificationEvents;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\FakesPush;
use Tests\TestCase;

/**
 * همه‌ی جاهایی که به مشتری/متخصص پیامک می‌دادند حالا از AppMessenger می‌گذرند (بسته‌ی ۴). حالت «آزمایشی» تا پیامک و پوش هر دو
 * دیده شوند؛ منطق «فعال» در AppMessengerTest.
 */
class PushSendersTest extends TestCase
{
    use FakesPush, RefreshDatabase;

    private Booking $booking;

    private User $customer;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->recordSms();
        $this->fakeFcm();
        PushMode::set(PushMode::SHADOW);

        $specialist = Specialist::factory()->create(['phone' => '09350000001']);
        $this->staff = User::find($specialist->user_id);
        $this->booking = Booking::factory()->create([
            'specialist_id' => $specialist->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'booking_time' => now()->addDay()->setTime(10, 0),
        ]);
        $this->customer = $this->booking->user;
    }

    private function messagesFor(User $user): \Illuminate\Support\Collection
    {
        return PushMessage::query()->where('user_id', $user->id)->get();
    }

    public function test_cancellation_by_the_salon_reaches_both_apps_as_urgent(): void
    {
        $this->deviceFor($this->customer);
        $this->deviceFor($this->staff, PushDevice::APP_STAFF);

        $this->booking->update(['status' => 'cancelled', 'cancelled_by' => 'admin', 'cancellation_reason' => 'تعطیلی']);

        $customerMessage = $this->messagesFor($this->customer)->firstWhere('event_key', NotificationEvents::BOOKING_CANCELLED_CUSTOMER);
        $staffMessage = $this->messagesFor($this->staff)->firstWhere('event_key', NotificationEvents::BOOKING_CANCELLED_SPECIALIST);
        $this->assertSame('urgent', $customerMessage->urgency);
        $this->assertSame('urgent', $staffMessage->urgency);
        $this->assertSame(['booking_id' => $this->booking->id], $customerMessage->data);
        // آزمایشی: پیامک هر دو مثل قبل رفت
        $this->assertEqualsCanonicalizing([$this->customer->phone, '09350000001'], collect($this->smsSent)->pluck('mobile')->unique()->values()->all());
    }

    public function test_cancellation_by_the_customer_is_not_urgent_for_the_customer(): void
    {
        $this->deviceFor($this->customer);

        $this->booking->update(['status' => 'cancelled', 'cancelled_by' => 'customer']);

        $this->assertSame('normal', $this->messagesFor($this->customer)->firstWhere('event_key', NotificationEvents::BOOKING_CANCELLED_CUSTOMER)->urgency);
    }

    public function test_reminder_job_pushes_to_customer_and_specialist_and_keeps_it_in_the_inbox(): void
    {
        $this->deviceFor($this->customer);
        $this->deviceFor($this->staff, PushDevice::APP_STAFF);

        (new SendBookingReminderJob($this->booking->id))->handle(app(\App\Services\SMSService::class), app(\App\Repositories\Contracts\BookingRepositoryInterface::class));

        $this->assertSame(PushPolicy::BOOKING_REMINDER_CUSTOMER, $this->messagesFor($this->customer)->sole()->event_key);
        $this->assertSame(PushPolicy::BOOKING_REMINDER_SPECIALIST, $this->messagesFor($this->staff)->sole()->event_key);
        $this->assertSame('urgent', $this->messagesFor($this->customer)->sole()->urgency);
        $this->assertSame(1, UserNotification::query()->where('notifiable_id', $this->customer->id)->where('type', 'App\\Notifications\\AppMessage')->count());
        $this->assertCount(2, $this->fcmSends());
    }

    public function test_notification_class_sms_becomes_a_push_for_the_specialist(): void
    {
        $this->deviceFor($this->staff, PushDevice::APP_STAFF);

        $this->booking->specialist->notifyNow(new BookingNotification($this->booking));

        $message = $this->messagesFor($this->staff)->sole();
        $this->assertSame(NotificationEvents::BOOKING_CREATED_SPECIALIST, $message->event_key);
        $this->assertSame('normal', $message->urgency);
        // متن پوش همان متن پیامک toSms است
        $this->assertSame($message->body, collect($this->smsSent)->firstWhere('mobile', '09350000001')['message']);
    }

    public function test_notification_class_with_sms_off_still_pushes_to_an_app_user(): void
    {
        app(NotificationSettingService::class)->isEnabled(NotificationEvents::BOOKING_CREATED_SPECIALIST, 'sms', $this->booking->salon_id);
        NotificationSetting::query()->where('event_key', NotificationEvents::BOOKING_CREATED_SPECIALIST)->update(['sms_enabled' => false]);
        app(NotificationSettingService::class)->flush($this->booking->salon_id);
        $this->deviceFor($this->staff, PushDevice::APP_STAFF);

        $this->booking->specialist->notifyNow(new BookingNotification($this->booking));

        $this->assertSame(1, $this->messagesFor($this->staff)->count());
        $this->assertNull($this->messagesFor($this->staff)->sole()->sms_text);
        $this->assertSame([], collect($this->smsSent)->where('mobile', '09350000001')->all());
    }

    public function test_notification_class_without_an_app_sends_the_same_sms_as_before(): void
    {
        $this->booking->specialist->notifyNow(new BookingNotification($this->booking));

        $this->assertSame(0, PushMessage::count());
        $this->assertCount(1, collect($this->smsSent)->where('mobile', '09350000001'));
    }

    public function test_discount_code_is_info_and_in_the_inbox(): void
    {
        $this->deviceFor($this->customer);

        DiscountCode::factory()->create(['user_id' => $this->customer->id, 'code' => 'PUSH10']);

        $message = $this->messagesFor($this->customer)->sole();
        $this->assertSame(PushPolicy::DISCOUNT_CODE_CUSTOMER, $message->event_key);
        $this->assertSame('info', $message->urgency);
        $this->assertStringContainsString('PUSH10', $message->body);
    }

    public function test_review_request_counts_as_sent_when_only_the_app_is_on(): void
    {
        $this->deviceFor($this->customer);
        app(NotificationSettingService::class)->isEnabled(NotificationEvents::BOOKING_COMPLETED_REVIEW_REQUEST, 'sms', $this->booking->salon_id);
        NotificationSetting::query()->where('event_key', NotificationEvents::BOOKING_COMPLETED_REVIEW_REQUEST)->update(['sms_enabled' => false]);
        app(NotificationSettingService::class)->flush($this->booking->salon_id);
        $this->booking->forceFill(['status' => 'completed'])->saveQuietly();

        $this->assertTrue(app(\App\Services\Review\ReviewService::class)->sendReviewRequest($this->booking->fresh()));

        $this->assertNotNull($this->booking->fresh()->review_sent_at);
        $this->assertSame(NotificationEvents::BOOKING_COMPLETED_REVIEW_REQUEST, $this->messagesFor($this->customer)->sole()->event_key);
        $this->assertSame([], collect($this->smsSent)->where('mobile', $this->customer->phone)->all());
    }
}
