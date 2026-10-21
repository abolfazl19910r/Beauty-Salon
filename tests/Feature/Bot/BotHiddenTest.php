<?php

namespace Tests\Feature\Bot;

use App\Jobs\SendBotMessageJob;
use App\Models\BotLink;
use App\Models\NotificationSetting;
use App\Models\Role;
use App\Models\User;
use App\Notifications\Booking\AdminNewBookingNotification;
use App\Services\Bot\BotMessenger;
use App\Services\Notification\NotificationSettingService;
use App\Support\CurrentSalon;
use App\Support\Notifications\NotificationEvents;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * بسته‌ی ۸ (۲۰۲۶-۱۰-۲۲): ربات کنار گذاشته شد ولی کدش ماند. با BOT_ENABLED خاموش (پیش‌فرض) — حتی با توکن، اتصال‌های قبلی و
 * تیک‌های ستون «ربات» — هیچ‌جا دیده نمی‌شود، اتصال و webhook ۴۰۴ اند، پیامی نمی‌رود و داده‌ها دست نمی‌خورند.
 */
class BotHiddenTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-webhook-secret-1234567890';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.bale.bot_token' => 'bale-token', 'services.bale.username' => 'MahruBot',
            'services.bot.webhook_secret' => self::SECRET,
        ]);
        Http::fake(['*' => Http::response(['ok' => true, 'result' => []])]);
    }

    public function test_the_switch_is_off_by_default(): void
    {
        $this->assertFalse(config('services.bot.enabled'));
        $this->assertFalse(NotificationSettingService::botChannelAvailable());
    }

    public function test_profiles_show_no_connect_card_and_connect_routes_are_404(): void
    {
        $customer = User::factory()->create(['user_type' => 'customer', 'salon_id' => app(CurrentSalon::class)->id()]);
        $slug = app(CurrentSalon::class)->get()->slug;

        $this->actingAs($customer)->get("/s/{$slug}/profile")->assertOk()->assertDontSee('اتصال به ربات')->assertDontSee('id="bot-connect"', false);
        $this->post("/s/{$slug}/profile/bot/bale")->assertNotFound();

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->post(route('admin.profile.bot.connect', 'bale'))->assertNotFound();
        $this->assertDatabaseCount('bot_link_codes', 0);
    }

    public function test_webhook_is_404_and_does_not_touch_existing_links(): void
    {
        $user = User::factory()->create();
        BotLink::create(['user_id' => $user->id, 'messenger' => 'bale', 'chat_id' => '555']);

        $this->postJson('/api/bot/webhook/bale/'.self::SECRET, ['message' => ['chat' => ['id' => '555', 'type' => 'private'], 'text' => '/stop']])
            ->assertNotFound();

        $this->assertSame(1, BotLink::count());
        Http::assertNothingSent();
    }

    public function test_no_message_goes_to_a_linked_chat_even_when_the_bot_column_is_ticked(): void
    {
        Bus::fake([SendBotMessageJob::class]);
        $admin = User::factory()->create(['is_admin' => true]);
        BotLink::create(['user_id' => $admin->id, 'messenger' => 'bale', 'chat_id' => '555']);
        NotificationSetting::query()->updateOrCreate(
            ['salon_id' => app(CurrentSalon::class)->id(), 'event_key' => NotificationEvents::BOOKING_CREATED_ADMIN],
            ['sms_enabled' => false, 'database_enabled' => true, 'telegram_enabled' => true]
        );
        app(NotificationSettingService::class)->flush();

        $this->assertSame(0, app(BotMessenger::class)->send($admin, 'سلام', NotificationEvents::BOOKING_CREATED_ADMIN, app(CurrentSalon::class)->id()));
        $this->assertNotContains('telegram', app(NotificationSettingService::class)->channels(NotificationEvents::BOOKING_CREATED_ADMIN, ['database', 'sms']));

        Bus::assertNotDispatched(SendBotMessageJob::class);
        $this->assertTrue(class_exists(AdminNewBookingNotification::class));
    }

    public function test_a_queued_bot_message_is_dropped_without_deleting_the_link(): void
    {
        $user = User::factory()->create();
        $link = BotLink::create(['user_id' => $user->id, 'messenger' => 'bale', 'chat_id' => '555']);

        (new SendBotMessageJob($link->id, 'پیام مانده در صف'))->handle(app(\App\Services\Bot\BotClient::class));

        Http::assertNothingSent();
        $this->assertDatabaseHas('bot_links', ['id' => $link->id]);
    }

    public function test_notification_settings_hide_the_bot_column_and_keep_its_saved_ticks(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $salonId = app(CurrentSalon::class)->id();
        NotificationSetting::query()->updateOrCreate(
            ['salon_id' => $salonId, 'event_key' => NotificationEvents::BOOKING_CREATED_ADMIN],
            ['sms_enabled' => false, 'database_enabled' => true, 'telegram_enabled' => true]
        );

        $this->actingAs($admin)->get(route('admin.notification-settings.index'))->assertOk()
            ->assertDontSee('data-bot-column', false)->assertDontSee('name="telegram[', false);

        $this->post(route('admin.notification-settings.update'), ['database' => ['booking__created__admin' => '1']])->assertSessionHas('success');

        $this->assertTrue((bool) NotificationSetting::where('salon_id', $salonId)->where('event_key', NotificationEvents::BOOKING_CREATED_ADMIN)->value('telegram_enabled'));

        // روشن کردن دوباره: ستون و تیک قبلی برمی‌گردد
        config(['services.bot.enabled' => true]);
        $this->get(route('admin.notification-settings.index'))->assertOk()->assertSee('data-bot-column', false);
    }

    public function test_super_admin_settings_hide_the_bot_section_and_its_form_is_404(): void
    {
        $super = User::factory()->create(['is_admin' => true]);
        $super->roles()->attach(Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']));
        $super->salons()->detach();

        $this->actingAs($super)->get(route('superadmin.settings.edit'))->assertOk()->assertDontSee('data-bot-status', false);
        $this->put(route('superadmin.settings.bot'), ['messenger' => 'bale', 'token' => '123456789:AbCdEfGhIjKl'])->assertNotFound();
        $this->assertDatabaseMissing('platform_settings', ['key' => 'bot_bale_token']);
    }

    public function test_bot_commands_say_the_bot_is_off(): void
    {
        $this->artisan('bot:poll', ['messenger' => 'bale', '--once' => true])->expectsOutputToContain('ربات خاموش است')->assertFailed();
        $this->artisan('bot:webhook', ['messenger' => 'bale'])->expectsOutputToContain('ربات خاموش است')->assertFailed();
        Http::assertNothingSent();
    }
}
