<?php

namespace Tests\Feature\Push;

use App\Models\PushDelivery;
use App\Models\PushMessage;
use App\Models\Role;
use App\Models\User;
use App\Services\Push\PushMode;
use App\Services\Push\PushStats;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\FakesPush;
use Tests\TestCase;

/**
 * تصمیم ۶-۱ ب (۲۰۲۶-۱۰-۲۲): کارت «آمادگی برای فعال» در صفحه‌ی پوش و پنجره‌ی تأیید با همان اعداد هنگام رفتن به «فعال».
 */
class PushReadinessTest extends TestCase
{
    use FakesPush, RefreshDatabase;

    private User $super;

    protected function setUp(): void
    {
        parent::setUp();
        $this->super = User::factory()->create(['is_admin' => true]);
        $this->super->roles()->attach(Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']));
        $this->super->salons()->detach();
        $this->fakeFcm();
    }

    private function deliveries(\App\Models\PushDevice $device, int $acked, int $unacked): void
    {
        foreach (array_merge(array_fill(0, $acked, true), array_fill(0, $unacked, false)) as $isAcked) {
            $m = PushMessage::create(['user_id' => $device->user_id, 'app' => $device->app, 'event_key' => 'push.test',
                'urgency' => 'info', 'mode' => 'shadow', 'title' => 't', 'body' => 'b']);
            PushDelivery::create(['push_message_id' => $m->id, 'push_device_id' => $device->id, 'status' => 'accepted',
                'accepted_at' => now()->subMinutes(5), 'acked_at' => $isAcked ? now()->subMinutes(5) : null]);
        }
    }

    private function customer(): User
    {
        return User::factory()->create(['salon_id' => app(CurrentSalon::class)->id(), 'user_type' => 'customer']);
    }

    public function test_ready_when_fcm_is_set_style_is_data_and_every_app_with_phones_acknowledges(): void
    {
        $device = $this->deviceFor($this->customer(), attributes: ['last_receipt_at' => now()]);
        $this->deliveries($device, 5, 1);

        $r = app(PushStats::class)->readiness();

        $this->assertTrue($r['ready']);
        $this->assertSame(6, $r['apps']['customer']['sent']);
        $this->assertSame(83.3, $r['apps']['customer']['ack_rate']);
        $this->assertSame(0, $r['apps']['staff']['devices'], 'اپ بی‌گوشی مسدود نمی‌کند');
    }

    public function test_not_ready_with_too_few_or_unacknowledged_pushes_or_notification_style(): void
    {
        $device = $this->deviceFor($this->customer());
        $this->deliveries($device, 2, 0);
        $this->assertFalse(app(PushStats::class)->readiness()['ready'], 'کمتر از حداقل ارسال');

        $this->deliveries($device, 0, 5);
        $this->assertFalse(app(PushStats::class)->readiness()['ready'], 'نرخ رسید پایین');

        $this->deliveries($device, 30, 0);
        $this->assertTrue(app(PushStats::class)->readiness()['ready']);

        config(['push.fcm.style' => 'notification']);
        $this->assertFalse(app(PushStats::class)->readiness()['ready']);

        config(['push.fcm.style' => 'data', 'push.fcm.credentials' => null]);
        $this->assertFalse(app(PushStats::class)->readiness()['ready']);
    }

    public function test_stats_page_shows_the_card_and_settings_ask_before_going_live(): void
    {
        $device = $this->deviceFor($this->customer());
        $this->deliveries($device, 0, 3);

        $this->actingAs($this->super)->get(route('superadmin.push.index'))->assertOk()
            ->assertSee('id="push-readiness"', false)->assertSee('data-push-ready="0"', false)
            ->assertSee('data-ready-check="fail"', false);

        $this->get(route('superadmin.settings.edit'))->assertOk()
            ->assertSee('data-live-confirm="هنوز آماده نیست.', false)
            ->assertSee('confirm(this.dataset.liveConfirm)', false);

        PushMode::set(PushMode::LIVE);
        $this->get(route('superadmin.push.index'))->assertOk()->assertDontSee('id="push-readiness"', false);
        $this->get(route('superadmin.settings.edit'))->assertOk()->assertDontSee('data-live-confirm', false);
    }
}
