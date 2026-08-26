<?php

namespace Tests\Unit\Services\Notification;

use App\Models\NotificationSetting;
use App\Services\Notification\NotificationSettingService;
use App\Support\Notifications\NotificationEvents;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationSettingServiceTest extends TestCase
{
    use RefreshDatabase;

    private NotificationSettingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(NotificationSettingService::class);
    }

    public function test_an_event_with_no_explicit_default_override_defaults_to_sms_and_database_on_telegram_off(): void
    {
        $this->assertTrue($this->service->isEnabled(NotificationEvents::BOOKING_CONFIRMED_CUSTOMER, 'sms'));
        $this->assertTrue($this->service->isEnabled(NotificationEvents::BOOKING_CONFIRMED_CUSTOMER, 'database'));
        $this->assertFalse($this->service->isEnabled(NotificationEvents::BOOKING_CONFIRMED_CUSTOMER, 'telegram'));

        $this->assertDatabaseHas('notification_settings', [
            'event_key' => NotificationEvents::BOOKING_CONFIRMED_CUSTOMER,
            'sms_enabled' => 1,
            'database_enabled' => 1,
            'telegram_enabled' => 0,
        ]);
    }

    /**
     * ⭐ These two are the exact duplicate-SMS bugs fixed in this session: booking creation (before
     * payment) and the post-completion "thank you" SMS. Both must default to SMS OFF so the fix
     * takes effect immediately on a fresh install, without any manual admin action.
     */
    public function test_known_duplicate_sms_events_default_to_sms_disabled(): void
    {
        $this->assertFalse($this->service->isEnabled(NotificationEvents::BOOKING_CREATED_CUSTOMER, 'sms'));
        $this->assertFalse($this->service->isEnabled(NotificationEvents::BOOKING_COMPLETED_CUSTOMER, 'sms'));
    }

    public function test_channels_filters_out_disabled_channels_from_the_notifications_own_base_set(): void
    {
        NotificationSetting::create([
            'event_key' => 'test.event',
            'sms_enabled' => false,
            'database_enabled' => true,
            'telegram_enabled' => false,
        ]);

        $channels = $this->service->channels('test.event', ['database', 'sms']);

        $this->assertSame(['database'], $channels);
    }

    public function test_channels_adds_telegram_when_enabled_even_if_not_in_the_base_set(): void
    {
        NotificationSetting::create([
            'event_key' => 'test.event',
            'sms_enabled' => true,
            'database_enabled' => true,
            'telegram_enabled' => true,
        ]);

        $channels = $this->service->channels('test.event', ['database', 'sms']);

        $this->assertSame(['database', 'sms', 'telegram'], $channels);
    }

    public function test_flush_forces_settings_to_be_re_read_from_the_database(): void
    {
        $this->assertTrue($this->service->isEnabled('test.event', 'sms'));

        NotificationSetting::where('event_key', 'test.event')->update(['sms_enabled' => false]);
        $this->service->flush();

        $this->assertFalse($this->service->isEnabled('test.event', 'sms'));
    }

    public function test_all_registered_event_keys_are_unique(): void
    {
        $keys = NotificationEvents::allKeys();

        $this->assertSame(count($keys), count(array_unique($keys)));
        $this->assertNotEmpty($keys);
    }
}
