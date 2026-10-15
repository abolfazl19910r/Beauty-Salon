<?php

namespace Tests\Feature\Push;

use App\Jobs\PushSmsFallbackJob;
use App\Jobs\SendPushJob;
use App\Models\PushDelivery;
use App\Models\PushDevice;
use App\Models\PushMessage;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Notification\NotificationSettingService;
use App\Services\Push\AppMessenger;
use App\Services\Push\Outgoing;
use App\Services\Push\PushMode;
use App\Services\Push\PushPolicy;
use App\Services\Push\PushUrgency;
use App\Support\CurrentSalon;
use App\Support\Features\Feature;
use App\Support\Notifications\NotificationEvents;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\FakesPush;
use Tests\TestCase;

/**
 * تصمیم «پوش یا پیامک» (بسته‌ی ۴؛ تصمیم ۲ گزینه‌ی «ج» و تصمیم‌های ۱ تا ۵ بسته‌ی ۴).
 */
class AppMessengerTest extends TestCase
{
    use FakesPush, RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->recordSms();
        $this->fakeFcm();
        $this->customer = User::factory()->create([
            'salon_id' => app(CurrentSalon::class)->id(), 'user_type' => 'customer', 'phone' => '09121110000',
        ]);
    }

    private function outgoing(string $key = NotificationEvents::BOOKING_CANCELLED_CUSTOMER, array $extra = []): Outgoing
    {
        return new Outgoing(...array_merge([
            'recipient' => $this->customer,
            'phone' => $this->customer->phone,
            'text' => 'سالن راستا: نوبت شما لغو شد.',
            'salonId' => $this->customer->salon_id,
            'smsFeature' => Feature::SmsCustomer,
            'pushKey' => $key,
            'settingsEvent' => in_array($key, NotificationEvents::allKeys(), true) ? $key : null,
            'data' => ['booking_id' => 42],
        ], $extra));
    }

    private function live(): void
    {
        PushMode::set(PushMode::LIVE);
        // تأخیر واقعی فقط روی صف غیرهمزمان معنا دارد
        config(['queue.default' => 'database']);
        Bus::fake([SendPushJob::class, PushSmsFallbackJob::class]);
    }

    public function test_without_an_app_the_sms_goes_exactly_as_before(): void
    {
        PushMode::set(PushMode::LIVE);

        $this->assertTrue(app(AppMessenger::class)->deliver($this->outgoing()));

        $this->assertCount(1, $this->smsSent);
        $this->assertSame(Feature::SmsCustomer, $this->smsSent[0]['feature']);
        $this->assertSame(0, PushMessage::count());
    }

    public function test_sms_switch_off_and_no_app_sends_nothing(): void
    {
        app(NotificationSettingService::class)->isEnabled(NotificationEvents::BOOKING_CANCELLED_CUSTOMER, 'sms');
        \App\Models\NotificationSetting::query()->where('event_key', NotificationEvents::BOOKING_CANCELLED_CUSTOMER)->update(['sms_enabled' => false]);
        app(NotificationSettingService::class)->flush();

        $this->assertFalse(app(AppMessenger::class)->deliver($this->outgoing()));
        $this->assertSame([], $this->smsSent);
    }

    public function test_off_mode_ignores_devices(): void
    {
        PushMode::set(PushMode::OFF);
        $this->deviceFor($this->customer);

        app(AppMessenger::class)->deliver($this->outgoing());

        $this->assertCount(1, $this->smsSent);
        $this->assertSame(0, PushMessage::count());
        $this->assertSame([], $this->fcmSends());
    }

    public function test_shadow_mode_sends_both_and_records_the_sms(): void
    {
        PushMode::set(PushMode::SHADOW);
        $device = $this->deviceFor($this->customer);

        app(AppMessenger::class)->deliver($this->outgoing());

        $this->assertCount(1, $this->smsSent);
        $message = PushMessage::sole();
        $this->assertSame('shadow', $message->sms_reason);
        $this->assertNotNull($message->sms_sent_at);
        $this->assertSame(PushMode::SHADOW, $message->mode);

        // صف sync: پوش همان لحظه به FCM رفت، data-only با شناسه‌ی پیام و نوبت
        $sends = $this->fcmSends();
        $this->assertCount(1, $sends);
        $this->assertSame($device->token, $sends[0]['message']['token']);
        $this->assertSame((string) $message->id, $sends[0]['message']['data']['message_id']);
        $this->assertSame('42', $sends[0]['message']['data']['booking_id']);
        $this->assertSame('لغو نوبت', $sends[0]['message']['data']['title']);
        $this->assertSame('سالن راستا: نوبت شما لغو شد.', $sends[0]['message']['data']['message']);
        $this->assertSame('bookings', $sends[0]['message']['data']['channelId']);
        // content.data اعلان در اپ (برای رسید و باز کردن صفحه‌ی نوبت)
        $this->assertSame(['message_id' => (string) $message->id, 'type' => 'booking.cancelled.customer', 'urgency' => 'urgent', 'booking_id' => '42', 'salon_id' => (string) $this->customer->salon_id],
            json_decode($sends[0]['message']['data']['body'], true));
        $this->assertArrayNotHasKey('notification', $sends[0]['message']);
        $this->assertSame('HIGH', $sends[0]['message']['android']['priority']);
        $this->assertSame(PushDelivery::ACCEPTED, PushDelivery::sole()->status);
    }

    public function test_live_urgent_waits_sixty_seconds_for_a_receipt(): void
    {
        $this->live();
        $this->deviceFor($this->customer);

        app(AppMessenger::class)->deliver($this->outgoing());

        $this->assertSame([], $this->smsSent);
        $message = PushMessage::sole();
        $this->assertSame('urgent', $message->urgency);
        $this->assertEqualsWithDelta(now()->addSeconds(60)->timestamp, $message->fallback_due_at->timestamp, 2);
        Bus::assertDispatched(SendPushJob::class, fn ($job) => $job->messageId === $message->id);
        Bus::assertDispatched(PushSmsFallbackJob::class, fn ($job) => $job->messageId === $message->id && $job->delay !== null);
    }

    public function test_live_normal_waits_ten_minutes_and_info_never_falls_back(): void
    {
        $this->live();
        $this->deviceFor($this->customer);

        app(AppMessenger::class)->deliver($this->outgoing(NotificationEvents::BOOKING_CONFIRMED_CUSTOMER));
        app(AppMessenger::class)->deliver($this->outgoing(PushPolicy::DISCOUNT_CODE_CUSTOMER));

        [$normal, $info] = [PushMessage::where('urgency', 'normal')->sole(), PushMessage::where('urgency', 'info')->sole()];
        $this->assertEqualsWithDelta(now()->addMinutes(10)->timestamp, $normal->fallback_due_at->timestamp, 2);
        $this->assertNull($info->fallback_due_at);
        Bus::assertDispatchedTimes(PushSmsFallbackJob::class, 1);
        $this->assertSame([], $this->smsSent);
    }

    public function test_fallback_job_sends_the_stored_sms_once_and_not_after_a_receipt(): void
    {
        $this->live();
        $this->deviceFor($this->customer);
        app(AppMessenger::class)->deliver($this->outgoing());
        app(AppMessenger::class)->deliver($this->outgoing(NotificationEvents::BOOKING_RESCHEDULED_CUSTOMER, ['text' => 'نوبت جابه‌جا شد']));
        [$unacked, $acked] = PushMessage::orderBy('created_at')->orderBy('event_key')->get()->partition(fn ($m) => $m->event_key === NotificationEvents::BOOKING_CANCELLED_CUSTOMER);
        $acked->first()->forceFill(['acked_at' => now()])->save();

        foreach ([$unacked->first(), $acked->first(), $unacked->first()] as $message) {
            (new PushSmsFallbackJob($message->id))->handle(app(AppMessenger::class));
        }

        $this->assertCount(1, $this->smsSent);
        $this->assertSame('سالن راستا: نوبت شما لغو شد.', $this->smsSent[0]['message']);
        $this->assertSame('no_receipt', $unacked->first()->fresh()->sms_reason);
        $this->assertNull($acked->first()->fresh()->sms_sent_at);
    }

    public function test_sms_switch_off_means_push_only_without_fallback(): void
    {
        $this->live();
        $this->deviceFor($this->customer);
        app(NotificationSettingService::class)->isEnabled(NotificationEvents::BOOKING_CANCELLED_CUSTOMER, 'sms');
        \App\Models\NotificationSetting::query()->where('event_key', NotificationEvents::BOOKING_CANCELLED_CUSTOMER)->update(['sms_enabled' => false]);
        app(NotificationSettingService::class)->flush();

        $this->assertTrue(app(AppMessenger::class)->deliver($this->outgoing()));

        $this->assertNull(PushMessage::sole()->sms_text);
        Bus::assertNotDispatched(PushSmsFallbackJob::class);
    }

    public function test_app_switch_off_keeps_the_old_sms(): void
    {
        $this->live();
        $this->deviceFor($this->customer);
        app(NotificationSettingService::class)->isEnabled(NotificationEvents::BOOKING_CANCELLED_CUSTOMER, 'push');
        \App\Models\NotificationSetting::query()->where('event_key', NotificationEvents::BOOKING_CANCELLED_CUSTOMER)->update(['push_enabled' => false]);
        app(NotificationSettingService::class)->flush();

        app(AppMessenger::class)->deliver($this->outgoing());

        $this->assertCount(1, $this->smsSent);
        $this->assertSame(0, PushMessage::count());
    }

    public function test_urgent_goes_straight_to_sms_when_the_last_two_pushes_had_no_receipt(): void
    {
        $this->live();
        $device = $this->deviceFor($this->customer);
        foreach ([1, 2] as $i) {
            $old = PushMessage::create(['user_id' => $this->customer->id, 'app' => 'customer', 'urgency' => 'urgent', 'mode' => 'live', 'title' => 't', 'body' => 'b']);
            PushDelivery::create(['push_message_id' => $old->id, 'push_device_id' => $device->id, 'status' => PushDelivery::ACCEPTED, 'accepted_at' => now()->subMinutes(10 * $i)]);
        }

        app(AppMessenger::class)->deliver($this->outgoing());

        $this->assertCount(1, $this->smsSent);
        $this->assertSame('unhealthy', PushMessage::latest('created_at')->where('event_key', NotificationEvents::BOOKING_CANCELLED_CUSTOMER)->sole()->sms_reason);
        // پوش هم می‌رود تا اولین رسید تازه دستگاه را سالم کند
        Bus::assertDispatchedTimes(SendPushJob::class, 1);

        // یک رسید تازه (مثلاً پوش آزمایشی) دستگاه را دوباره سالم می‌کند
        $this->smsSent = [];
        $device->forceFill(['last_receipt_at' => now()])->save();
        app(AppMessenger::class)->deliver($this->outgoing());
        $this->assertSame([], $this->smsSent);
    }

    public function test_one_healthy_device_is_enough(): void
    {
        $this->live();
        $sick = $this->deviceFor($this->customer);
        $this->deviceFor($this->customer, attributes: ['manufacturer' => 'samsung']);
        foreach ([1, 2] as $i) {
            $old = PushMessage::create(['user_id' => $this->customer->id, 'app' => 'customer', 'urgency' => 'urgent', 'mode' => 'live', 'title' => 't', 'body' => 'b']);
            PushDelivery::create(['push_message_id' => $old->id, 'push_device_id' => $sick->id, 'status' => PushDelivery::ACCEPTED, 'accepted_at' => now()->subMinutes(10 * $i)]);
        }

        app(AppMessenger::class)->deliver($this->outgoing());

        $this->assertSame([], $this->smsSent);
        $this->assertSame(2, PushMessage::latest('created_at')->where('event_key', NotificationEvents::BOOKING_CANCELLED_CUSTOMER)->sole()->deliveries()->count());
    }

    public function test_server_outage_sends_the_sms_right_away_and_keeps_retrying_the_push(): void
    {
        PushMode::set(PushMode::LIVE);
        config(['queue.default' => 'database']);
        Bus::fake([PushSmsFallbackJob::class]);
        $this->fakeFcm(['oauth2.googleapis.com/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('Could not resolve host: oauth2.googleapis.com')]);
        $this->deviceFor($this->customer);

        app(AppMessenger::class)->deliver($this->outgoing());
        $message = PushMessage::sole();
        $this->artisan('queue:work', ['--once' => true, '--queue' => 'push', '--stop-when-empty' => true]);

        $this->assertCount(1, $this->smsSent);
        $this->assertSame('outage', $message->fresh()->sms_reason);
        $this->assertSame(PushDelivery::FAILED, PushDelivery::sole()->status);
        // job با فاصله دوباره صف شد، نه شکست قطعی
        $this->assertSame(1, \Illuminate\Support\Facades\DB::table('jobs')->where('queue', 'push')->count());
    }

    public function test_unregistered_token_disables_the_device(): void
    {
        PushMode::set(PushMode::SHADOW);
        $this->fakeFcm(['fcm.googleapis.com/*' => Http::response(['error' => [
            'code' => 404, 'status' => 'NOT_FOUND', 'message' => 'Requested entity was not found.',
            'details' => [['@type' => 'type.googleapis.com/google.firebase.fcm.v1.FcmError', 'errorCode' => 'UNREGISTERED']],
        ]], 404)]);
        $device = $this->deviceFor($this->customer);

        app(AppMessenger::class)->deliver($this->outgoing());

        $this->assertSame(PushDelivery::INVALID, PushDelivery::sole()->status);
        $this->assertSame('invalid_token', $device->fresh()->disabled_reason);
        $this->assertFalse(app(AppMessenger::class)->hasDevices($this->customer, $this->customer->salon_id, NotificationEvents::BOOKING_CANCELLED_CUSTOMER));
    }

    public function test_expired_google_token_is_refreshed_once(): void
    {
        PushMode::set(PushMode::SHADOW);
        $sends = 0;
        $this->fakeFcm(['fcm.googleapis.com/*' => function () use (&$sends) {
            return ++$sends === 1 ? Http::response(['error' => ['status' => 'UNAUTHENTICATED']], 401) : Http::response(['name' => 'ok']);
        }]);
        $this->deviceFor($this->customer);

        app(AppMessenger::class)->deliver($this->outgoing());

        $this->assertSame(2, $sends);
        $this->assertSame(PushDelivery::ACCEPTED, PushDelivery::sole()->status);
        $oauth = collect(Http::recorded())->filter(fn ($p) => str_contains($p[0]->url(), 'oauth2'))->count();
        $this->assertSame(2, $oauth);
    }

    public function test_google_access_token_is_cached_between_messages(): void
    {
        PushMode::set(PushMode::SHADOW);
        $this->deviceFor($this->customer);

        app(AppMessenger::class)->deliver($this->outgoing());
        app(AppMessenger::class)->deliver($this->outgoing());

        $this->assertSame(1, collect(Http::recorded())->filter(fn ($p) => str_contains($p[0]->url(), 'oauth2'))->count());
        $this->assertCount(2, $this->fcmSends());
    }

    public function test_messages_that_were_sms_only_also_land_in_the_app_inbox(): void
    {
        $this->live();
        $this->deviceFor($this->customer);

        app(AppMessenger::class)->deliver($this->outgoing(PushPolicy::DISCOUNT_CODE_CUSTOMER, ['inbox' => true, 'text' => 'کد تخفیف شما: NOROOZ']));

        $row = UserNotification::query()->where('notifiable_id', $this->customer->id)->sole();
        $this->assertSame('کد تخفیف شما: NOROOZ', $row->data['message']);
        $this->assertSame('کد تخفیف', $row->data['title']);
        $this->assertSame($this->customer->salon_id, (int) $row->salon_id);
    }

    public function test_without_firebase_credentials_nothing_is_pushed(): void
    {
        $this->live();
        config(['push.fcm.credentials' => null]);
        $this->deviceFor($this->customer);

        app(AppMessenger::class)->deliver($this->outgoing());

        $this->assertCount(1, $this->smsSent);
        $this->assertSame(0, PushMessage::count());
    }

    public function test_sync_queue_falls_back_to_sms_immediately(): void
    {
        PushMode::set(PushMode::LIVE);
        $this->deviceFor($this->customer);

        app(AppMessenger::class)->deliver($this->outgoing());

        $this->assertCount(1, $this->smsSent);
        $this->assertCount(1, $this->fcmSends());
    }

    public function test_disabled_device_and_devices_of_another_salon_are_skipped(): void
    {
        $this->live();
        $this->deviceFor($this->customer, attributes: ['disabled_at' => now(), 'disabled_reason' => 'invalid_token']);

        app(AppMessenger::class)->deliver($this->outgoing());

        $this->assertCount(1, $this->smsSent);
        $this->assertSame(0, PushMessage::count());
    }

    public function test_urgency_can_be_lowered_by_the_caller(): void
    {
        $this->live();
        $this->deviceFor($this->customer);

        app(AppMessenger::class)->deliver($this->outgoing(extra: ['urgency' => PushUrgency::Normal]));

        $this->assertSame('normal', PushMessage::sole()->urgency);
    }

    public function test_specialist_recipient_reaches_the_staff_account_devices(): void
    {
        $this->live();
        $specialist = \App\Models\Specialist::factory()->create();
        $staff = User::find($specialist->user_id);
        $this->deviceFor($staff, PushDevice::APP_STAFF);

        app(AppMessenger::class)->deliver(new Outgoing($specialist, $specialist->phone, 'یادآوری', $specialist->salon_id, Feature::SmsReminders, PushPolicy::BOOKING_REMINDER_SPECIALIST));

        $this->assertSame($staff->id, (int) PushMessage::sole()->user_id);
        $this->assertSame('staff', PushMessage::sole()->app);
        $this->assertSame([], $this->smsSent);
    }

    public function test_a_single_unacknowledged_push_does_not_make_a_device_unhealthy(): void
    {
        $this->live();
        $device = $this->deviceFor($this->customer);
        $old = PushMessage::create(['user_id' => $this->customer->id, 'app' => 'customer', 'urgency' => 'urgent', 'mode' => 'live', 'title' => 't', 'body' => 'b']);
        PushDelivery::create(['push_message_id' => $old->id, 'push_device_id' => $device->id, 'status' => PushDelivery::ACCEPTED, 'accepted_at' => now()->subMinutes(5)]);

        app(AppMessenger::class)->deliver($this->outgoing());

        $this->assertSame([], $this->smsSent);
    }

    public function test_receipt_arriving_while_the_fallback_runs_wins(): void
    {
        $this->live();
        $this->deviceFor($this->customer);
        app(AppMessenger::class)->deliver($this->outgoing());
        $stale = PushMessage::sole();
        // رسید درست بین خواندن پیام در job و ادعای ارسال رسید
        PushMessage::query()->whereKey($stale->id)->update(['acked_at' => now()]);

        $this->assertFalse(app(AppMessenger::class)->fallbackNow($stale, 'no_receipt'));
        $this->assertSame([], $this->smsSent);
    }
}
