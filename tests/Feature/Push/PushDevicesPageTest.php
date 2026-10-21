<?php

namespace Tests\Feature\Push;

use App\Models\AppRelease;
use App\Models\PushDelivery;
use App\Models\PushMessage;
use App\Models\Role;
use App\Models\User;
use App\Services\Push\AppMessenger;
use App\Services\Push\PushMode;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\FakesPush;
use Tests\TestCase;

/**
 * بسته‌ی ۷ (۲۰۲۶-۱۰-۲۲): «دستگاه‌ها»ی پوش در ابرمدیر — فهرست با وضعیت و فیلتر، نسخه‌ی اپ روی گوشی‌ها، جزئیات با ارسال‌های آخر،
 * پوش آزمایشی و «خروج این دستگاه» (ابطال توکن). فقط ابرمدیر. توکن FCM هرگز در صفحه نیست.
 */
class PushDevicesPageTest extends TestCase
{
    use FakesPush, RefreshDatabase;

    private User $super;

    protected function setUp(): void
    {
        parent::setUp();
        $this->super = User::factory()->create(['is_admin' => true]);
        $this->super->roles()->attach(Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']));
        $this->super->salons()->detach();
    }

    private function customer(string $phone): User
    {
        return User::factory()->create(['salon_id' => app(CurrentSalon::class)->id(), 'user_type' => 'customer', 'phone' => $phone]);
    }

    public function test_list_shows_status_and_filters_without_leaking_the_fcm_token(): void
    {
        $acked = $this->deviceFor($this->customer('09121110001'), attributes: ['last_receipt_at' => now(), 'app_version' => '0.3.1']);
        $never = $this->deviceFor($this->customer('09121110002'), attributes: ['app_version' => '0.3.0', 'manufacturer' => 'samsung', 'model' => 'SM-A175F']);
        $disabled = $this->deviceFor($this->customer('09121110003'), attributes: ['disabled_at' => now(), 'disabled_reason' => 'invalid_token']);
        $expired = $this->deviceFor($this->customer('09121110004'));
        $expired->accessToken()->first()->forceFill(['expires_at' => now()->subDay()])->save();

        $page = $this->actingAs($this->super)->get(route('superadmin.push.devices.index'))->assertOk();
        $page->assertSee('data-device-row="'.$acked->id.'"', false)
            ->assertSee('data-status="active"', false)->assertSee('data-status="never_acked"', false)
            ->assertSee('data-status="disabled"', false)->assertSee('data-status="expired"', false)
            ->assertSee('توکن باطل')
            ->assertDontSee($acked->token);

        $only = fn (array $q) => $this->get(route('superadmin.push.devices.index', $q))->assertOk();
        $only(['status' => 'never_acked'])->assertSee('data-device-row="'.$never->id.'"', false)->assertDontSee('data-device-row="'.$acked->id.'"', false);
        $only(['status' => 'active'])->assertSee('data-device-row="'.$acked->id.'"', false)->assertDontSee('data-device-row="'.$never->id.'"', false);
        $only(['status' => 'expired'])->assertSee('data-device-row="'.$expired->id.'"', false)->assertDontSee('data-device-row="'.$disabled->id.'"', false);
        $only(['status' => 'disabled'])->assertSee('data-device-row="'.$disabled->id.'"', false)->assertDontSee('data-device-row="'.$expired->id.'"', false);
        $only(['phone' => '۰۹۱۲۱۱۱۰۰۰۲'])->assertSee('data-device-row="'.$never->id.'"', false)->assertDontSee('data-device-row="'.$acked->id.'"', false);
        $only(['maker' => 'samsung'])->assertSee('data-device-row="'.$never->id.'"', false)->assertDontSee('data-device-row="'.$acked->id.'"', false);
        $only(['app' => 'staff'])->assertDontSee('data-device-row="'.$acked->id.'"', false);
    }

    public function test_version_card_counts_phones_behind_the_latest_release(): void
    {
        AppRelease::create(['variant' => 'customer', 'version_name' => '0.3.1', 'version_code' => 9, 'file_path' => 'x.apk', 'file_size' => 1, 'sha256' => str_repeat('a', 64), 'is_published' => true]);
        $this->deviceFor($this->customer('09121110001'), attributes: ['app_version' => '0.3.1']);
        $this->deviceFor($this->customer('09121110002'), attributes: ['app_version' => '0.3.0']);
        $this->deviceFor($this->customer('09121110003'), attributes: ['app_version' => '0.2.5']);

        $this->actingAs($this->super)->get(route('superadmin.push.devices.index'))->assertOk()
            ->assertSee('data-app-versions="customer"', false)
            ->assertSeeInOrder(['data-outdated', '۲ گوشی هنوز به‌روز نشده'], false);
    }

    public function test_detail_page_lists_recent_deliveries_and_flags_an_unhealthy_phone(): void
    {
        $device = $this->deviceFor($this->customer('09121110001'));
        foreach (range(1, 2) as $i) {
            $m = PushMessage::create(['user_id' => $device->user_id, 'app' => 'customer', 'event_key' => 'booking.cancelled.customer',
                'urgency' => 'urgent', 'mode' => 'live', 'title' => 't', 'body' => 'b']);
            PushDelivery::create(['push_message_id' => $m->id, 'push_device_id' => $device->id, 'status' => 'accepted', 'accepted_at' => now()->subMinutes(10)]);
        }

        $this->assertTrue(AppMessenger::isUnhealthy($device));
        $this->actingAs($this->super)->get(route('superadmin.push.devices.show', $device))->assertOk()
            ->assertSee('data-unhealthy', false)
            ->assertSee('data-delivery="accepted"', false)
            ->assertDontSee($device->token);

        $device->forceFill(['last_receipt_at' => now()])->save();
        $this->assertFalse(AppMessenger::isUnhealthy($device->fresh()));
    }

    public function test_test_push_goes_to_this_device_only_without_sms(): void
    {
        $this->fakeFcm();
        $this->recordSms();
        $device = $this->deviceFor($this->customer('09121110001'));
        $other = $this->deviceFor($this->customer('09121110002'));

        $this->actingAs($this->super)->post(route('superadmin.push.devices.test', $device))->assertSessionHas('success');

        $this->assertSame(1, PushDelivery::count());
        $this->assertDatabaseHas('push_deliveries', ['push_device_id' => $device->id]);
        $this->assertDatabaseMissing('push_deliveries', ['push_device_id' => $other->id]);
        $this->assertCount(1, $this->fcmSends());
        $this->assertSame([], $this->smsSent);

        PushMode::set(PushMode::OFF);
        $this->post(route('superadmin.push.devices.test', $device))->assertSessionHasErrors('push');
        $this->assertSame(1, PushDelivery::count());
    }

    public function test_sign_out_revokes_the_app_session_and_is_audited(): void
    {
        $user = $this->customer('09121110001');
        $device = $this->deviceFor($user);
        $keep = $this->deviceFor($user);
        $tokenId = $device->personal_access_token_id;

        $this->actingAs($this->super)->delete(route('superadmin.push.devices.sign-out', $device))
            ->assertRedirect(route('superadmin.push.devices.index'))->assertSessionHas('success');

        $this->assertDatabaseMissing('push_devices', ['id' => $device->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
        $this->assertDatabaseHas('push_devices', ['id' => $keep->id]);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseHas('superadmin_audit_logs', ['action' => 'push.device_signed_out']);
    }

    public function test_only_super_admin_reaches_the_devices_pages(): void
    {
        $device = $this->deviceFor($this->customer('09121110001'));
        $owner = User::factory()->create(['is_admin' => true]);

        $this->actingAs($owner)->get(route('superadmin.push.devices.index'))->assertStatus(403);
        $this->get(route('superadmin.push.devices.show', $device))->assertStatus(403);
        $this->delete(route('superadmin.push.devices.sign-out', $device))->assertStatus(403);
        $this->assertDatabaseHas('push_devices', ['id' => $device->id]);
    }
}
