<?php

namespace Tests\Feature\Push;

use App\Models\PushDelivery;
use App\Models\PushMessage;
use App\Models\User;
use App\Services\Push\PushMode;
use App\Services\Push\PushStats;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Sleep;
use Tests\Concerns\FakesPush;
use Tests\TestCase;

/**
 * ابزار سبز کردن کارت «آمادگی برای فعال» و آزمون حالت «فعال» (تصمیم‌های ۱ تا ۴-الف، ۲۰۲۶-۱۰-۲۲): push:test --repeat،
 * push:live-check و پیش‌بررسی محیط (حالت، صف sync، worker).
 */
class PushLiveCheckTest extends TestCase
{
    use FakesPush, RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = User::factory()->create(['salon_id' => app(CurrentSalon::class)->id(), 'user_type' => 'customer', 'phone' => '09121110000']);
        $this->fakeFcm();
        $this->recordSms();
    }

    private function ackAll(): void
    {
        PushDelivery::query()->whereNull('acked_at')->where('status', 'accepted')->update(['acked_at' => now()]);
        PushMessage::query()->whereNull('acked_at')->update(['acked_at' => now()]);
    }

    public function test_sends_needed_to_pass_the_card(): void
    {
        // اعداد کارت ابوالفضل (۲۰۲۶-۱۰-۲۲): مشتری ۱۱ از ۱۸، همکار ۱۸ از ۲۸
        $this->assertSame(17, PushStats::sendsNeeded(18, 11));
        $this->assertSame(22, PushStats::sendsNeeded(28, 18));
        $this->assertSame(5, PushStats::sendsNeeded(0, 0));
        $this->assertSame(3, PushStats::sendsNeeded(2, 2), 'نرخ کافی است ولی دست‌کم ۵ ارسال لازم است');
        $this->assertSame(0, PushStats::sendsNeeded(10, 8));
    }

    public function test_push_test_repeats_with_a_gap_and_prints_receipts_and_the_card(): void
    {
        Sleep::fake();
        $device = $this->deviceFor($this->customer, attributes: ['last_receipt_at' => now()]);

        // گوشی‌ای که اپ از رویش پاک شده: ارسال‌های بی‌رسید قدیمی‌اش نرخ را پایین می‌کشد
        $gone = $this->deviceFor($this->customer, attributes: ['disabled_at' => now(), 'manufacturer' => 'samsung']);
        $old = PushMessage::create(['user_id' => $this->customer->id, 'app' => 'customer', 'event_key' => 'push.test', 'urgency' => 'info', 'mode' => 'shadow', 'title' => 't', 'body' => 'b']);
        PushDelivery::create(['push_message_id' => $old->id, 'push_device_id' => $gone->id, 'status' => 'accepted', 'accepted_at' => now()->subDay()]);

        Sleep::whenFakingSleep(fn () => PushDelivery::query()->where('push_device_id', $device->id)->whereNull('acked_at')->update(['acked_at' => now()]));

        $this->artisan('push:test', ['phone' => '09121110000', '--repeat' => 3, '--gap' => 5, '--wait' => 10])
            ->assertSuccessful()
            ->expectsOutputToContain('رسید در همین اجرا')
            ->expectsOutputToContain('۳ از ۳')
            ->expectsOutputToContain('آمادگی برای فعال')
            ->expectsOutputToContain('اپ customer: دست‌کم ۱ ارسال تازه‌ی دیگر با رسید لازم است (از ارسال‌های بی‌رسید این بازه، ۱ تا به گوشی‌های غیرفعال‌شده بوده)');

        $this->assertSame(3, PushMessage::where('event_key', 'push.test')->where('id', '!=', $old->id)->count());
        $this->assertSame(3, PushDelivery::where('push_device_id', $device->id)->count());
        $this->assertCount(3, $this->fcmSends());
        Sleep::assertSlept(fn ($d) => (int) $d->totalSeconds === 5, 2);
        $this->assertSame([], $this->smsSent, 'پوش آزمایشی پیامک ندارد');
    }

    public function test_push_test_warns_when_a_worker_is_not_picking_jobs(): void
    {
        config(['queue.default' => 'database']);
        DB::table('jobs')->insert(['queue' => 'push', 'payload' => '{}', 'attempts' => 0, 'reserved_at' => null,
            'available_at' => now()->subMinutes(2)->getTimestamp(), 'created_at' => now()->subMinutes(2)->getTimestamp()]);
        $this->deviceFor($this->customer);

        $this->artisan('push:test', ['phone' => '09121110000'])->assertSuccessful()
            ->expectsOutputToContain('روی صف «push» بیش از ۳۰ ثانیه برداشته نشده — worker باز نیست یا این صف را نمی‌خواند: php artisan queue:work --queue=otp,push,sms');
    }

    public function test_live_check_refuses_when_push_is_off(): void
    {
        PushMode::set(PushMode::OFF);
        $this->deviceFor($this->customer);

        $this->artisan('push:live-check', ['phone' => '09121110000'])->assertFailed()->expectsOutputToContain('«خاموش»');
        $this->assertSame(0, PushMessage::count());
    }

    public function test_live_info_message_is_push_only(): void
    {
        PushMode::set(PushMode::LIVE);
        Sleep::fake();
        Sleep::whenFakingSleep(fn () => $this->ackAll());
        $this->deviceFor($this->customer);

        $this->artisan('push:live-check', ['phone' => '۰۹۱۲۱۱۱۰۰۰۰', '--urgency' => 'info'])
            ->assertSuccessful()->expectsOutputToContain('فقط پوش است');

        $this->assertSame('push.live_check', PushMessage::sole()->event_key);
        $this->assertSame([], $this->smsSent);
    }

    public function test_sync_queue_is_flagged_because_the_fallback_goes_at_once(): void
    {
        PushMode::set(PushMode::LIVE);
        $this->deviceFor($this->customer);

        $this->artisan('push:live-check', ['phone' => '09121110000', '--urgency' => 'urgent', '--wait' => 0])
            ->assertFailed()->expectsOutputToContain('QUEUE_CONNECTION=sync')->expectsOutputToContain('مهلت آزموده نشد');

        $this->assertCount(1, $this->smsSent);
        $this->assertNull($this->smsSent[0]['salon_id'], 'پیامک آزمون از سهمیه‌ی سالن کم نمی‌شود');
    }

    public function test_live_receipt_in_time_saves_the_sms(): void
    {
        PushMode::set(PushMode::LIVE);
        config(['queue.default' => 'database']);
        Queue::fake();
        Sleep::fake();
        // worker پوش را فرستاد و اپ چند ثانیه بعد رسید داد
        Sleep::whenFakingSleep(function () {
            PushDelivery::query()->update(['status' => 'accepted', 'accepted_at' => now()]);
            $this->ackAll();
        });
        $this->deviceFor($this->customer);

        $this->artisan('push:live-check', ['phone' => '09121110000', '--urgency' => 'urgent'])
            ->assertSuccessful()->expectsOutputToContain('پیامک صرفه‌جویی‌شده');

        $this->assertSame([], $this->smsSent);
        Queue::assertPushed(\App\Jobs\PushSmsFallbackJob::class);
    }

    public function test_live_check_names_a_missing_push_and_sms_worker(): void
    {
        PushMode::set(PushMode::LIVE);
        config(['queue.default' => 'database', 'push.fallback_seconds.normal' => 1]);
        Queue::fake();
        Sleep::fake(syncWithCarbon: true);
        $this->deviceFor($this->customer);

        $this->artisan('push:live-check', ['phone' => '09121110000', '--urgency' => 'normal', '--wait' => 25])
            ->assertFailed()
            ->expectsOutputToContain('worker صف push کار نمی‌کند')
            ->expectsOutputToContain('worker صف sms کار نمی‌کند')
            ->expectsOutputToContain('نه رسید آمد نه پیامک');
    }

    public function test_unhealthy_device_gets_the_urgent_message_as_sms_at_once(): void
    {
        PushMode::set(PushMode::LIVE);
        config(['queue.default' => 'database']);
        Queue::fake();
        $device = $this->deviceFor($this->customer);
        foreach ([1, 2] as $i) {
            $m = PushMessage::create(['user_id' => $this->customer->id, 'app' => 'customer', 'urgency' => 'urgent', 'mode' => 'live', 'title' => 't', 'body' => 'b']);
            PushDelivery::create(['push_message_id' => $m->id, 'push_device_id' => $device->id, 'status' => 'accepted', 'accepted_at' => now()->subMinutes(10)]);
        }

        $this->artisan('push:live-check', ['phone' => '09121110000', '--wait' => 0])
            ->assertSuccessful()->expectsOutputToContain('دستگاه ناسالم بود');

        $this->assertCount(1, $this->smsSent);
    }

    public function test_shadow_mode_is_flagged_as_not_testing_live(): void
    {
        PushMode::set(PushMode::SHADOW);
        $this->deviceFor($this->customer);

        $this->artisan('push:live-check', ['phone' => '09121110000', '--app' => 'customer', '--wait' => 0])
            ->assertFailed()->expectsOutputToContain('حالت «آزمایشی» است')->expectsOutputToContain('مسیر «فعال» آزموده نشد');

        // info هم در «آزمایشی» پیامک می‌شود؛ متن نباید «هرگز پیامک» بگوید (یافته‌ی آزمون ابوالفضل ۲۰۲۶-۱۰-۲۲)
        $this->artisan('push:live-check', ['phone' => '09121110000', '--urgency' => 'info', '--wait' => 0])
            ->assertFailed()->expectsOutputToContain('حالت «آزمایشی» است')->expectsOutputToContain('در «آزمایشی» پیامک همان لحظه هم می‌رود');
        $this->assertCount(2, $this->smsSent);

        $this->artisan('push:live-check', ['phone' => '09121110000', '--app' => 'staff'])->assertFailed()->expectsOutputToContain('در اپ staff');
        $this->artisan('push:live-check', ['phone' => '09121110000', '--urgency' => 'later'])->assertFailed()->expectsOutputToContain('urgent، normal یا info');
    }
}
