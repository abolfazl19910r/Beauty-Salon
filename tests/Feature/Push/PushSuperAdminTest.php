<?php

namespace Tests\Feature\Push;

use App\Models\PlatformSetting;
use App\Models\PushDelivery;
use App\Models\PushMessage;
use App\Models\Role;
use App\Models\User;
use App\Services\Bot\BotClient;
use App\Services\Bot\BotSettings;
use App\Services\Push\PushMode;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\FakesPush;
use Tests\TestCase;

/**
 * ابرمدیر (بسته‌ی ۴): کلید حالت پوش، صفحه‌ی «اعلان اپ (پوش)» و توکن ربات بله/تلگرام از «تنظیمات پلتفرم».
 */
class PushSuperAdminTest extends TestCase
{
    use FakesPush, RefreshDatabase;

    private User $super;

    protected function setUp(): void
    {
        parent::setUp();
        $this->super = User::factory()->create(['is_admin' => true]);
        $this->super->roles()->attach(Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']));
        $this->super->salons()->detach();
        config(['services.bale.bot_token' => null, 'services.bale.username' => null, 'services.telegram.bot_token' => null]);
    }

    public function test_push_mode_defaults_to_shadow_and_is_switched_from_settings(): void
    {
        $this->assertSame(PushMode::SHADOW, PushMode::current());

        $this->actingAs($this->super)->get(route('superadmin.settings.edit'))->assertOk()
            ->assertSee('data-push-settings', false)->assertSee('PUSH_FCM_CREDENTIALS');

        $this->put(route('superadmin.settings.push'), ['mode' => 'live'])->assertRedirect()->assertSessionHas('success');
        $this->assertSame(PushMode::LIVE, PushMode::current());
        $this->assertDatabaseHas('superadmin_audit_logs', ['action' => 'push.mode']);

        $this->put(route('superadmin.settings.push'), ['mode' => 'turbo'])->assertSessionHasErrors('mode');
        $this->assertSame(PushMode::LIVE, PushMode::current());
    }

    public function test_only_super_admin_reaches_push_settings(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);

        $this->actingAs($owner)->put(route('superadmin.settings.push'), ['mode' => 'off']);
        $this->assertSame(PushMode::SHADOW, PushMode::current());
        $this->get(route('superadmin.push.index'))->assertStatus(403);
    }

    public function test_fcm_check_button_reports_the_project(): void
    {
        $this->fakeFcm();

        $this->actingAs($this->super)->post(route('superadmin.settings.push.check'))
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'mahru-test'));

        $this->fakeFcm(['oauth2.googleapis.com/*' => Http::response(['error' => 'invalid_grant'], 400)]);
        $this->post(route('superadmin.settings.push.check'))->assertSessionHasErrors('push');
    }

    public function test_stats_page_shows_receipt_rate_sms_and_makers(): void
    {
        $this->recordSms();
        $customer = User::factory()->create(['salon_id' => app(CurrentSalon::class)->id(), 'user_type' => 'customer', 'phone' => '09121110000']);
        $device = $this->deviceFor($customer);
        foreach ([[true, null], [true, null], [false, 'no_receipt'], [false, null]] as $i => [$acked, $reason]) {
            $m = PushMessage::create([
                'user_id' => $customer->id, 'app' => 'customer', 'event_key' => 'booking.cancelled.customer', 'urgency' => 'urgent', 'mode' => 'live',
                'title' => 'لغو نوبت', 'body' => 'b', 'sms_text' => 'b', 'sms_parts' => 2,
                'acked_at' => $acked ? now()->addSeconds(5 + $i) : null, 'sms_sent_at' => $reason ? now() : null, 'sms_reason' => $reason,
            ]);
            PushDelivery::create(['push_message_id' => $m->id, 'push_device_id' => $device->id, 'status' => 'accepted', 'accepted_at' => now(), 'acked_at' => $acked ? now() : null]);
        }
        PushMode::set(PushMode::LIVE);

        $html = $this->actingAs($this->super)->get(route('superadmin.push.index', ['days' => 30]))->assertOk()
            ->assertSee('data-ack-rate', false)->assertSee('Xiaomi')->assertSee('بدون رسید در مهلت')->getContent();

        $this->assertStringContainsString('۵۰٪', $html);            // ۲ از ۴
        $this->assertStringContainsString('پیامک صرفه‌جویی‌شده', $html);
        $this->actingAs($this->super)->get(route('superadmin.dashboard'))->assertSee('اعلان اپ (پوش)');
    }

    public function test_bale_token_is_checked_encrypted_and_used_by_the_bot(): void
    {
        Http::fake(['tapi.bale.ai/bot123456:AbcdefGhijkLmnop/getMe' => Http::response(['ok' => true, 'result' => ['username' => 'Mahru_Bot']])]);

        $this->actingAs($this->super)->put(route('superadmin.settings.bot'), ['messenger' => 'bale', 'token' => '123456:AbcdefGhijkLmnop'])
            ->assertSessionHas('success');

        $stored = PlatformSetting::query()->where('key', 'bot_bale_token')->value('value');
        $this->assertNotSame('123456:AbcdefGhijkLmnop', $stored);   // رمزنگاری‌شده
        $this->assertSame('123456:AbcdefGhijkLmnop', BotSettings::token('bale'));
        $this->assertSame('Mahru_Bot', BotSettings::username('bale'));
        $this->assertSame(['bale'], app(BotClient::class)->enabledMessengers());
        $this->assertSame('https://ble.ir/Mahru_Bot?start=X1', app(BotClient::class)->deepLink('bale', 'X1'));

        // صفحه فقط چهار رقم آخر را نشان می‌دهد
        $this->get(route('superadmin.settings.edit'))->assertSee('••••mnop')->assertDontSee('123456:AbcdefGhijkLmnop');

        // پیام ربات با همان توکن پنل
        Http::fake(['tapi.bale.ai/*' => Http::response(['ok' => true])]);
        app(BotClient::class)->sendMessage('bale', '42', 'سلام');
        Http::assertSent(fn ($r) => str_contains($r->url(), '/bot123456:AbcdefGhijkLmnop/sendMessage'));
    }

    public function test_rejected_token_is_not_saved_and_unreachable_one_is_saved_with_a_warning(): void
    {
        Http::fake([
            'tapi.bale.ai/*' => Http::response(['ok' => false, 'description' => 'Unauthorized'], 401),
            'api.telegram.org/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('timed out'),
        ]);

        $this->actingAs($this->super)->put(route('superadmin.settings.bot'), ['messenger' => 'bale', 'token' => '111:WrongTokenWrong'])
            ->assertSessionHasErrors('bot_bale');
        $this->assertNull(BotSettings::token('bale'));

        $this->put(route('superadmin.settings.bot'), ['messenger' => 'telegram', 'token' => '222:TelegramTokenAbc', 'username' => '@mahru_bot'])
            ->assertSessionHas('warning');
        $this->assertSame('222:TelegramTokenAbc', BotSettings::token('telegram'));
        $this->assertSame('mahru_bot', BotSettings::username('telegram'));

        $this->put(route('superadmin.settings.bot'), ['messenger' => 'bale', 'token' => 'not a token'])->assertSessionHasErrors('token');
    }

    public function test_telegram_proxy_and_removal_fall_back_to_env(): void
    {
        config(['services.telegram.bot_token' => '999:FromEnvFileToken', 'services.telegram.username' => 'env_bot']);
        Http::fake(['tg.example.com/*' => Http::response(['ok' => true, 'result' => ['username' => 'panel_bot']])]);

        $this->actingAs($this->super)->put(route('superadmin.settings.bot'), [
            'messenger' => 'telegram', 'token' => '333:PanelTokenForTg', 'api_base' => 'https://tg.example.com/secret123',
        ])->assertSessionHas('success');
        Http::assertSent(fn ($r) => $r->url() === 'https://tg.example.com/secret123/bot333:PanelTokenForTg/getMe');
        $this->assertSame('panel', BotSettings::tokenSource('telegram'));
        $this->assertSame('https://tg.example.com/secret123', BotSettings::apiBase('telegram'));

        $this->put(route('superadmin.settings.bot'), ['messenger' => 'telegram', 'remove' => 1])->assertSessionHas('success');
        $this->assertSame('env', BotSettings::tokenSource('telegram'));
        $this->assertSame('999:FromEnvFileToken', BotSettings::token('telegram'));
        $this->assertSame('env_bot', BotSettings::username('telegram'));
        $this->assertSame('https://api.telegram.org', BotSettings::apiBase('telegram'));
    }
}
