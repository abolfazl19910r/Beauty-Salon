<?php

namespace Tests\Feature\Bot;

use App\Jobs\SendBotMessageJob;
use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\BotLink;
use App\Models\BotLinkCode;
use App\Models\Specialist;
use App\Models\User;
use App\Notifications\Booking\AdminNewBookingNotification;
use App\Services\Bot\BotLinkService;
use App\Services\Bot\BotMessenger;
use App\Support\CurrentSalon;
use App\Support\Notifications\NotificationEvents;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * ربات پلتفرم بله/تلگرام (۲۰۲۶-۱۰-۰۱): اتصال با «/start <کد یک‌بارمصرف>»، قطع اتصال، و اعلان فقط به گفت‌وگوی خود گیرنده.
 */
class PlatformBotTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-webhook-secret-1234567890';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.bale.bot_token' => 'bale-token', 'services.bale.username' => 'MahruBot',
            'services.telegram.bot_token' => null,
            'services.bot.webhook_secret' => self::SECRET,
        ]);
        Http::fake(fn ($request) => match (true) {
            $this->blocked => Http::response(['ok' => false, 'error_code' => 403, 'description' => 'Forbidden: bot was blocked by the user'], 403),
            str_ends_with($request->url(), '/getUpdates') && $this->updates !== null => Http::response($this->updates),
            default => Http::response(['ok' => true, 'result' => []]),
        });
    }

    private ?array $updates = null;

    private bool $blocked = false;

    private function webhook(string $text, string $chatId = '555', string $messenger = 'bale', string $secret = self::SECRET)
    {
        return $this->postJson("/api/bot/webhook/{$messenger}/{$secret}", ['message' => ['chat' => ['id' => $chatId, 'type' => 'private'], 'text' => $text]]);
    }

    private function sentTexts(): array
    {
        return collect(Http::recorded())->map(fn ($pair) => $pair[0]['text'] ?? null)->filter()->values()->all();
    }

    public function test_a_user_links_a_chat_with_a_one_time_code_from_the_profile(): void
    {
        $customer = User::factory()->create(['user_type' => 'customer', 'salon_id' => app(CurrentSalon::class)->id()]);
        $slug = app(CurrentSalon::class)->get()->slug;

        $this->actingAs($customer)->get("/s/{$slug}/profile")->assertOk()->assertSee('اتصال به ربات');
        $connect = $this->actingAs($customer)->post("/s/{$slug}/profile/bot/bale")->assertRedirect();
        $code = $connect->getSession()->get('bot_connect')['code'];
        $this->assertSame('https://ble.ir/MahruBot?start='.$code, $connect->getSession()->get('bot_connect')['url']);
        $this->assertDatabaseMissing('bot_link_codes', ['code_hash' => $code]); // فقط hash ذخیره می‌شود

        $this->webhook("/start {$code}")->assertOk();

        $this->assertDatabaseHas('bot_links', ['user_id' => $customer->id, 'messenger' => 'bale', 'chat_id' => '555']);
        $this->assertStringContainsString('اتصال انجام شد', $this->sentTexts()[0]);

        // یک‌بارمصرف: همان کد برای گفت‌وگوی دیگر کار نمی‌کند
        $this->webhook("/start {$code}", '777');
        $this->assertDatabaseMissing('bot_links', ['chat_id' => '777']);
    }

    public function test_an_expired_code_is_refused_and_a_new_code_replaces_the_old_one(): void
    {
        $user = User::factory()->create();
        $links = app(BotLinkService::class);
        $old = $links->createCode($user, null);
        $new = $links->createCode($user, null);

        $this->assertNull($links->consume('bale', '1', $old), 'old code replaced');
        $this->travel(11)->minutes();
        $this->assertNull($links->consume('bale', '1', $new), 'expired');
        $this->assertSame(0, BotLink::count());
    }

    public function test_stop_unlinks_the_chat_and_a_wrong_secret_is_404(): void
    {
        $user = User::factory()->create();
        BotLink::create(['user_id' => $user->id, 'messenger' => 'bale', 'chat_id' => '555']);

        $this->webhook('/stop', '555', 'bale', 'wrong-secret')->assertNotFound();
        $this->assertSame(1, BotLink::count());

        $this->webhook('/stop')->assertOk();
        $this->assertSame(0, BotLink::count());

        $this->webhook('/start telegramcode', '1', 'telegram')->assertNotFound(); // تلگرام خاموش (بدون توکن)
    }

    public function test_staff_connect_and_disconnect_from_the_admin_and_specialist_profiles(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->post(route('admin.profile.bot.connect', 'bale'))->assertSessionHas('bot_connect');
        $this->assertSame(1, BotLinkCode::where('user_id', $admin->id)->count());

        BotLink::create(['user_id' => $admin->id, 'messenger' => 'bale', 'chat_id' => '9']);
        $this->actingAs($admin)->delete(route('admin.profile.bot.disconnect', 'bale'))->assertSessionHas('success');
        $this->assertSame(0, BotLink::count());

        $this->actingAs($admin)->post(route('admin.profile.bot.connect', 'telegram'))->assertNotFound();
    }

    public function test_a_notification_goes_only_to_the_recipients_own_linked_chat_with_the_salon_name(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $other = User::factory()->create(['is_admin' => true]);
        $booking = Booking::factory()->create(['specialist_id' => Specialist::factory()->create()->id, 'service_id' => BeautyService::factory()->create()->id]);
        BotLink::create(['user_id' => $owner->id, 'messenger' => 'bale', 'chat_id' => '100']);
        BotLink::create(['user_id' => $other->id, 'messenger' => 'bale', 'chat_id' => '200']);

        $owner->notifyNow(new AdminNewBookingNotification($booking));

        $sent = collect(Http::recorded())->map(fn ($p) => [$p[0]['chat_id'], $p[0]['text']]);
        $this->assertSame(['100'], $sent->pluck(0)->all());
        $this->assertStringStartsWith('«'.app(CurrentSalon::class)->get()->name.'»', $sent[0][1]);
    }

    public function test_the_bot_channel_is_skipped_for_recipients_without_a_link_and_when_the_event_is_off(): void
    {
        Bus::fake([SendBotMessageJob::class]);
        $user = User::factory()->create();
        $booking = Booking::factory()->create(['specialist_id' => Specialist::factory()->create()->id, 'service_id' => BeautyService::factory()->create()->id]);

        $this->assertNotContains('telegram', (new AdminNewBookingNotification($booking))->via($user));

        BotLink::create(['user_id' => $user->id, 'messenger' => 'bale', 'chat_id' => '1']);
        $this->assertContains('telegram', (new AdminNewBookingNotification($booking))->via($user));

        \App\Models\NotificationSetting::where('event_key', NotificationEvents::BOOKING_CONFIRMED_CUSTOMER)->delete();
        \App\Models\NotificationSetting::create(['event_key' => NotificationEvents::BOOKING_CONFIRMED_CUSTOMER, 'sms_enabled' => true, 'database_enabled' => true, 'telegram_enabled' => false, 'salon_id' => app(CurrentSalon::class)->id()]);
        app(\App\Services\Notification\NotificationSettingService::class)->flush(app(CurrentSalon::class)->id());
        $this->assertSame(0, app(BotMessenger::class)->send($user, 'x', NotificationEvents::BOOKING_CONFIRMED_CUSTOMER, app(CurrentSalon::class)->id()));
        Bus::assertNotDispatched(SendBotMessageJob::class);
    }

    public function test_a_blocked_bot_removes_the_link(): void
    {
        $this->blocked = true;
        $user = User::factory()->create();
        $link = BotLink::create(['user_id' => $user->id, 'messenger' => 'bale', 'chat_id' => '1']);

        (new SendBotMessageJob($link->id, 'سلام'))->handle(app(\App\Services\Bot\BotClient::class));

        $this->assertSame(0, BotLink::count());
    }

    public function test_a_specialist_gets_the_message_through_the_user_of_the_specialist(): void
    {
        Bus::fake([SendBotMessageJob::class]);
        $user = User::factory()->create();
        $specialist = Specialist::factory()->create(['user_id' => $user->id]);
        BotLink::create(['user_id' => $user->id, 'messenger' => 'bale', 'chat_id' => '1']);

        $this->assertSame(1, app(BotMessenger::class)->send($specialist, 'یادآوری', null, app(CurrentSalon::class)->id()));
        Bus::assertDispatched(SendBotMessageJob::class, fn ($job) => str_contains($job->text, 'یادآوری'));
    }

    public function test_the_webhook_command_registers_the_secret_url(): void
    {
        $this->artisan('bot:webhook', ['messenger' => 'bale'])->assertSuccessful();

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/botbale-token/setWebhook')
            && str_ends_with($request['url'], '/api/bot/webhook/bale/'.self::SECRET));
    }

    public function test_the_booking_reminder_also_goes_to_the_linked_customer_and_specialist(): void
    {
        Bus::fake([SendBotMessageJob::class]);
        $customer = User::factory()->create(['user_type' => 'customer', 'salon_id' => app(CurrentSalon::class)->id()]);
        $specialistUser = User::factory()->create();
        $specialist = Specialist::factory()->create(['user_id' => $specialistUser->id]);
        $booking = Booking::factory()->create(['user_id' => $customer->id, 'specialist_id' => $specialist->id, 'service_id' => BeautyService::factory()->create()->id, 'status' => 'confirmed', 'booking_time' => now()->addHour()]);
        BotLink::create(['user_id' => $customer->id, 'messenger' => 'bale', 'chat_id' => 'c']);
        BotLink::create(['user_id' => $specialistUser->id, 'messenger' => 'bale', 'chat_id' => 's']);
        $this->mock(\App\Services\SMSService::class)->shouldReceive('send')->andReturn(true);

        app()->call([new \App\Jobs\SendBookingReminderJob($booking->id), 'handle']);

        Bus::assertDispatchedTimes(SendBotMessageJob::class, 2);
    }

    public function test_bot_poll_links_a_chat_without_a_public_webhook_for_local_testing(): void
    {
        $user = User::factory()->create();
        $code = app(BotLinkService::class)->createCode($user, null);
        $this->updates = ['ok' => true, 'result' => [
            ['update_id' => 41, 'message' => ['chat' => ['id' => 321, 'type' => 'private'], 'text' => "/start {$code}"]],
        ]];

        $this->artisan('bot:poll', ['messenger' => 'bale', '--once' => true])->assertSuccessful();

        $this->assertDatabaseHas('bot_links', ['user_id' => $user->id, 'messenger' => 'bale', 'chat_id' => '321']);
        $this->assertStringContainsString('اتصال انجام شد', $this->sentTexts()[0]);
    }

    public function test_bot_poll_explains_that_a_registered_webhook_must_be_removed_first(): void
    {
        $this->updates = ['ok' => false, 'error_code' => 409, 'description' => "Conflict: can't use getUpdates method while webhook is active"];

        $this->artisan('bot:poll', ['messenger' => 'bale', '--once' => true])
            ->expectsOutputToContain('bot:webhook bale --delete')
            ->assertFailed();
    }
}
