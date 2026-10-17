<?php

namespace Tests\Feature\Push;

use App\Jobs\SendPushJob;
use App\Models\PushDelivery;
use App\Models\PushDevice;
use App\Models\PushMessage;
use App\Models\Specialist;
use App\Models\User;
use App\Services\Push\PushMode;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\Concerns\FakesPush;
use Tests\TestCase;

/**
 * ثبت گوشی برای پوش، رسید و پوش آزمایشی (بسته‌ی ۴) — مشترک دو اپ.
 */
class PushDeviceApiTest extends TestCase
{
    use FakesPush, RefreshDatabase;

    private User $customer;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeFcm();
        $this->customer = User::factory()->create(['salon_id' => app(CurrentSalon::class)->id(), 'user_type' => 'customer', 'phone' => '09121110000']);
        $this->token = $this->customer->createToken('RN8', ['customer'], now()->addDays(90))->plainTextToken;
    }

    private function api(?string $token = null): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token ?? $this->token);
    }

    private function register(string $fcm = 'fcm-AAA-111', ?string $token = null)
    {
        return $this->api($token)->putJson('/api/v1/push/device', [
            'token' => $fcm, 'app_version' => '0.2.0', 'manufacturer' => 'Xiaomi', 'model' => 'Redmi Note 8', 'os_version' => '11',
        ]);
    }

    public function test_customer_registers_this_session(): void
    {
        $this->register()->assertOk()->assertJsonPath('data.registered', true)->assertJsonPath('data.server_enabled', true);

        $device = PushDevice::sole();
        $this->assertSame('customer', $device->app);
        $this->assertSame($this->customer->salon_id, (int) $device->salon_id);
        $this->assertSame(PersonalAccessToken::findToken($this->token)->id, (int) $device->personal_access_token_id);
        $this->assertSame('Xiaomi', $device->manufacturer);
        $this->assertArrayNotHasKey('token', $device->toArray());

        // دوباره ثبت (توکن FCM تازه شد) همان ردیف را به‌روز می‌کند
        $this->register('fcm-BBB-222')->assertOk();
        $this->assertSame('fcm-BBB-222', PushDevice::sole()->token);
    }

    public function test_staff_app_registers_as_staff(): void
    {
        $specialist = Specialist::factory()->create();
        $staffToken = User::find($specialist->user_id)->createToken('A17', ['staff'], now()->addDays(90))->plainTextToken;

        $this->register('fcm-staff', $staffToken)->assertOk();

        $this->assertSame('staff', PushDevice::sole()->app);
        $this->assertSame($specialist->user_id, PushDevice::sole()->user_id);
    }

    public function test_same_phone_token_on_another_account_stops_reaching_the_old_one(): void
    {
        $this->register('shared-fcm-token')->assertOk();
        $other = User::factory()->create(['salon_id' => $this->customer->salon_id, 'user_type' => 'customer', 'phone' => '09121110001']);
        $otherToken = $other->createToken('RN8', ['customer'], now()->addDays(90))->plainTextToken;

        $this->register('shared-fcm-token', $otherToken)->assertOk();

        $this->assertSame($other->id, PushDevice::sole()->user_id);
    }

    public function test_logout_and_token_revocation_remove_the_device(): void
    {
        $this->register()->assertOk();
        $this->api()->postJson('/api/v1/logout')->assertOk();
        $this->assertSame(0, PushDevice::count());

        $token = $this->customer->createToken('RN8', ['customer'], now()->addDays(90))->plainTextToken;
        $this->register('fcm-second-token', $token)->assertOk();
        $this->customer->forceFill(['password' => bcrypt('new-pass-123')])->save(); // ابطال خودکار بسته‌ی ۱
        $this->assertSame(0, PushDevice::count());
    }

    public function test_logout_removes_a_device_that_already_has_deliveries(): void
    {
        $this->register()->assertOk();
        $this->api()->postJson('/api/v1/push/test')->assertOk();
        $this->artisan('push:test', ['phone' => '09121110000'])->assertSuccessful()->expectsOutputToContain('ثبت (ورود)');
        $this->assertGreaterThan(0, PushDelivery::count());

        $this->api()->postJson('/api/v1/logout')->assertOk();

        $this->assertSame(0, PushDevice::count());
        $this->artisan('push:test', ['phone' => '09121110000'])->assertFailed()->expectsOutputToContain('هیچ گوشی فعالی');
    }

    public function test_device_of_an_expired_or_missing_session_gets_no_push(): void
    {
        $this->register()->assertOk();
        $this->assertSame(1, PushDevice::query()->active()->count());

        PersonalAccessToken::query()->update(['expires_at' => now()->subMinute()]);
        $this->assertSame(0, PushDevice::query()->active()->count());
        $this->artisan('push:test', ['phone' => '09121110000'])->assertFailed();

        // سرور بدون کلید خارجی (cascade): ردیف می‌ماند ولی فعال حساب نمی‌شود
        PersonalAccessToken::query()->update(['expires_at' => null]);
        \Illuminate\Support\Facades\Schema::withoutForeignKeyConstraints(fn () => PersonalAccessToken::query()->delete());
        $this->assertSame(0, PushDevice::query()->active()->count());
        $this->assertFalse(app(\App\Services\Push\AppMessenger::class)->hasDevices($this->customer, $this->customer->salon_id, null));
    }

    public function test_registration_loop_of_an_old_build_does_not_starve_the_rest_of_the_api(): void
    {
        // اپ 0.2.0: PUT /push/device در حلقه (گزارش ۲۰۲۶-۱۰-۱۷)
        for ($i = 0; $i < 12; $i++) {
            $this->register()->assertOk();
        }
        for ($i = 0; $i < 120; $i++) {   // بیشتر از کل سهمیه‌ی api-v1 (۱۲۰ در دقیقه)
            $this->register()->assertStatus(429);
        }

        // بقیه‌ی اپ از سهمیه‌ی api-v1 جداست
        $this->api()->getJson('/api/v1/me')->assertOk();
        $this->assertSame(1, PushDevice::count());
    }

    public function test_delete_and_show(): void
    {
        $this->api()->getJson('/api/v1/push/device')->assertOk()->assertJsonPath('data.registered', false);
        $this->register()->assertOk();
        $this->api()->getJson('/api/v1/push/device')->assertJsonPath('data.registered', true)->assertJsonPath('data.provider', 'fcm');
        $this->api()->deleteJson('/api/v1/push/device')->assertOk();
        $this->assertSame(0, PushDevice::count());
    }

    public function test_validation_and_unavailable_ntfy(): void
    {
        $this->api()->putJson('/api/v1/push/device', ['token' => ''])->assertStatus(422);
        $this->api()->putJson('/api/v1/push/device', ['token' => 'topic-abcdef', 'provider' => 'ntfy'])
            ->assertStatus(422)->assertJsonPath('error.code', 'push_provider_unavailable');

        config(['push.ntfy.server' => 'http://192.168.1.2:8090']);
        $this->api()->putJson('/api/v1/push/device', ['token' => 'topic-abcdef', 'provider' => 'ntfy'])->assertOk()->assertJsonPath('data.provider', 'ntfy');
    }

    public function test_requires_a_bearer_token(): void
    {
        $this->putJson('/api/v1/push/device', ['token' => 'x'])->assertUnauthorized();
        $this->postJson('/api/v1/push/receipts', ['ids' => []])->assertUnauthorized();
    }

    public function test_receipts_acknowledge_only_own_messages_from_this_device(): void
    {
        $this->register()->assertOk();
        $device = PushDevice::sole();
        $mine = PushMessage::create(['user_id' => $this->customer->id, 'app' => 'customer', 'urgency' => 'urgent', 'mode' => 'live', 'title' => 't', 'body' => 'b']);
        PushDelivery::create(['push_message_id' => $mine->id, 'push_device_id' => $device->id, 'status' => PushDelivery::ACCEPTED, 'accepted_at' => now()]);
        $stranger = User::factory()->create(['salon_id' => $this->customer->salon_id, 'user_type' => 'customer', 'phone' => '09121110009']);
        $theirs = PushMessage::create(['user_id' => $stranger->id, 'app' => 'customer', 'urgency' => 'urgent', 'mode' => 'live', 'title' => 't', 'body' => 'b']);

        $this->api()->postJson('/api/v1/push/receipts', ['ids' => [$mine->id, $theirs->id], 'opened' => true])
            ->assertOk()->assertJsonPath('data.acknowledged', 1);

        $this->assertNotNull($mine->fresh()->acked_at);
        $this->assertNull($theirs->fresh()->acked_at);
        $delivery = PushDelivery::sole();
        $this->assertNotNull($delivery->acked_at);
        $this->assertNotNull($delivery->opened_at);
        $this->assertNotNull($device->fresh()->last_receipt_at);

        $this->api()->postJson('/api/v1/push/receipts', ['ids' => ['not-a-uuid']])->assertStatus(422);
    }

    public function test_test_push_goes_to_this_device_only_and_is_rate_limited(): void
    {
        Bus::fake([SendPushJob::class]);
        $this->api()->postJson('/api/v1/push/test')->assertStatus(409)->assertJsonPath('error.code', 'push_not_registered');
        $this->register()->assertOk();

        $this->api()->postJson('/api/v1/push/test')->assertOk();

        $message = PushMessage::sole();
        $this->assertSame('push.test', $message->event_key);
        $this->assertNull($message->sms_text);
        Bus::assertDispatched(SendPushJob::class);

        // سقف ۳ در دقیقه (درخواست ردشده‌ی اول هم شمرده شد)
        $this->api()->postJson('/api/v1/push/test')->assertOk();
        $this->api()->postJson('/api/v1/push/test')->assertStatus(429);
    }

    public function test_test_push_refused_when_platform_push_is_off(): void
    {
        $this->register()->assertOk();
        PushMode::set(PushMode::OFF);

        $this->api()->postJson('/api/v1/push/test')->assertStatus(409)->assertJsonPath('error.code', 'push_unavailable');
        $this->api()->getJson('/api/v1/push/device')->assertJsonPath('data.server_enabled', false);
    }
}
