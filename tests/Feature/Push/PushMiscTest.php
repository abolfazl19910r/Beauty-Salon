<?php

namespace Tests\Feature\Push;

use App\Models\NotificationSetting;
use App\Models\PushDelivery;
use App\Models\PushMessage;
use App\Models\User;
use App\Services\Push\NtfyClient;
use App\Services\Push\PushMode;
use App\Services\Push\PushUrgency;
use App\Support\CurrentSalon;
use App\Support\Notifications\NotificationEvents;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\FakesPush;
use Tests\TestCase;

/**
 * ستون «اپ» تنظیمات اعلان، دستور push:test، سبک «notification» FCM، فرستنده‌ی ntfy و پاک‌سازی (بسته‌ی ۴).
 */
class PushMiscTest extends TestCase
{
    use FakesPush, RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = User::factory()->create(['salon_id' => app(CurrentSalon::class)->id(), 'user_type' => 'customer', 'phone' => '09121110000']);
    }

    public function test_app_column_is_shown_only_for_customer_and_specialist_events_and_saved(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/admin/notification-settings')->assertOk()
            ->assertSee('data-push-toggle="'.NotificationEvents::BOOKING_CANCELLED_CUSTOMER.'"', false)
            ->assertSee('data-push-toggle="'.NotificationEvents::LEAVE_STATUS_SPECIALIST.'"', false)
            ->assertDontSee('data-push-toggle="'.NotificationEvents::PAYMENT_RECEIVED_ADMIN.'"', false);

        $safe = str_replace('.', '__', NotificationEvents::BOOKING_CONFIRMED_CUSTOMER);
        $this->post('/admin/notification-settings', ['sms' => [$safe => 1], 'push' => []])->assertRedirect();

        $salonId = app(CurrentSalon::class)->id();
        $this->assertFalse(NotificationSetting::where('salon_id', $salonId)->where('event_key', NotificationEvents::BOOKING_CONFIRMED_CUSTOMER)->value('push_enabled'));
        // رویداد مدیر ستون «اپ» ندارد و دست‌نخورده (روشن) می‌ماند
        $this->assertTrue((bool) NotificationSetting::where('salon_id', $salonId)->where('event_key', NotificationEvents::PAYMENT_RECEIVED_ADMIN)->value('push_enabled'));
    }

    public function test_push_test_command_sends_to_every_phone_of_a_number(): void
    {
        $this->fakeFcm();
        $this->deviceFor($this->customer);
        $this->deviceFor($this->customer, attributes: ['manufacturer' => 'samsung', 'model' => 'SM-A175F']);

        $this->artisan('push:test', ['phone' => '۰۹۱۲۱۱۱۰۰۰۰'])->assertSuccessful()->expectsOutputToContain('سالم');

        $this->assertSame(2, PushMessage::where('event_key', 'push.test')->count());
        $this->assertSame(2, PushDelivery::where('status', 'accepted')->count());
        $this->assertCount(2, $this->fcmSends());
    }

    public function test_push_test_command_explains_missing_setup(): void
    {
        config(['push.fcm.credentials' => null]);
        $this->artisan('push:test', ['phone' => '09121110000'])->assertFailed()->expectsOutputToContain('PUSH_FCM_CREDENTIALS');

        $this->fakeFcm();
        $this->artisan('push:test', ['phone' => '09129999999'])->assertFailed()->expectsOutputToContain('هیچ گوشی فعالی');

        config(['push.fcm.credentials' => '/nowhere/key.json']);
        $this->artisan('push:test', ['phone' => '09121110000'])->assertFailed()->expectsOutputToContain('پیدا نشد');
    }

    public function test_notification_style_lets_android_build_the_notification(): void
    {
        config(['push.fcm.style' => 'notification']);
        $this->fakeFcm();
        $device = $this->deviceFor($this->customer);

        app(\App\Services\Push\AppMessenger::class)->sendTest($device, 'سلام');

        $message = $this->fcmSends()[0]['message'];
        $this->assertSame(['title' => 'پوش آزمایشی ماهرو', 'body' => 'سلام'], $message['notification']);
        $this->assertSame('general', $message['android']['notification']['channel_id']);
    }

    public function test_ntfy_publishes_json_with_the_message_id(): void
    {
        config(['push.ntfy.server' => 'http://192.168.1.2:8090', 'push.ntfy.token' => 'tk_abc']);
        Http::fake(['192.168.1.2:8090' => Http::response(['id' => 'x'])]);

        $result = app(NtfyClient::class)->send('mahru-topic-1', 'لغو نوبت', 'نوبت شما لغو شد', ['message_id' => 'abc-123'], PushUrgency::Urgent);

        $this->assertTrue($result->ok);
        Http::assertSent(fn ($r) => $r['topic'] === 'mahru-topic-1' && $r['title'] === 'لغو نوبت' && $r['priority'] === 5
            && str_ends_with($r['message'], '[m:abc-123]') && $r->hasHeader('Authorization', 'Bearer tk_abc'));
    }

    public function test_old_push_messages_are_pruned(): void
    {
        $old = PushMessage::create(['user_id' => $this->customer->id, 'app' => 'customer', 'urgency' => 'info', 'mode' => PushMode::LIVE, 'title' => 't', 'body' => 'b']);
        PushMessage::query()->whereKey($old->id)->update(['created_at' => now()->subDays(91)]);
        $fresh = PushMessage::create(['user_id' => $this->customer->id, 'app' => 'customer', 'urgency' => 'info', 'mode' => PushMode::LIVE, 'title' => 't', 'body' => 'b']);

        $this->artisan('model:prune', ['--model' => [PushMessage::class]])->assertSuccessful();

        $this->assertSame([$fresh->id], PushMessage::pluck('id')->all());
    }
}
