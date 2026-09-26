<?php

namespace Tests\Feature\Notification;

use App\Models\Booking;
use App\Models\NotificationSetting;
use App\Models\User;
use App\Notifications\Booking\AdminNewBookingNotification;
use App\Services\Notification\NotificationSettingService;
use App\Support\CurrentSalon;
use App\Support\Notifications\NotificationEvents;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * کانال ربات (تلگرام/بله) هنوز پیاده‌سازی نشده (تصمیم ۲۰۲۶-۰۹-۲۷): نه باید قابل روشن‌کردن باشد، نه هیچ
 * اعلانی از آن عبور کند — حتی اگر ردیف قدیمی‌ای آن را روشن کرده باشد.
 */
class TelegramChannelDisabledTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->admin->salons()->syncWithoutDetaching([app(CurrentSalon::class)->id() => ['role' => 'owner']]);
    }

    public function test_a_legacy_row_with_the_bot_on_never_yields_the_telegram_channel(): void
    {
        NotificationSetting::create([
            'event_key' => NotificationEvents::BOOKING_CREATED_ADMIN,
            'sms_enabled' => true,
            'database_enabled' => true,
            'telegram_enabled' => true,
        ]);

        $service = app(NotificationSettingService::class);

        $this->assertFalse($service->isEnabled(NotificationEvents::BOOKING_CREATED_ADMIN, 'telegram'));
        $this->assertNotContains('telegram', $service->channels(NotificationEvents::BOOKING_CREATED_ADMIN, ['database', 'sms']));

        $booking = Booking::factory()->create();
        $via = (new AdminNewBookingNotification($booking))->via($this->admin);
        $this->assertNotContains('telegram', $via);
        $this->assertContains('database', $via);
    }

    public function test_a_direct_request_enabling_the_bot_is_rejected_and_nothing_is_saved(): void
    {
        $key = NotificationEvents::BOOKING_CREATED_SPECIALIST;
        $safeKey = str_replace('.', '__', $key);

        NotificationSetting::create([
            'event_key' => $key, 'sms_enabled' => true, 'database_enabled' => true, 'telegram_enabled' => false,
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/notification-settings', [
            'database' => [$safeKey => '1'],
            'telegram' => [$safeKey => '1'],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['telegram' => 'این قابلیت هنوز پیاده‌سازی نشده است.']);
        $response->assertSessionMissing('success');

        $this->assertDatabaseHas('notification_settings', [
            'event_key' => $key, 'sms_enabled' => 1, 'database_enabled' => 1, 'telegram_enabled' => 0,
        ]);
    }

    public function test_the_settings_page_shows_the_bot_column_disabled(): void
    {
        NotificationSetting::create([
            'event_key' => NotificationEvents::BOOKING_CREATED_ADMIN,
            'sms_enabled' => true, 'database_enabled' => true, 'telegram_enabled' => true,
        ]);

        $html = $this->actingAs($this->admin)->get('/admin/notification-settings')->assertOk()->getContent();

        $this->assertStringContainsString('این قابلیت هنوز پیاده‌سازی نشده است', $html);
        preg_match_all('/<input[^>]*name="telegram\[[^"]+\]"[^>]*>/', $html, $inputs);
        $this->assertNotEmpty($inputs[0]);
        foreach ($inputs[0] as $input) {
            $this->assertStringContainsString('disabled', $input);
            $this->assertStringNotContainsString('checked', $input);
        }
    }
}
