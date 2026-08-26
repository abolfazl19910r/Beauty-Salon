<?php

namespace Tests\Feature\Admin;

use App\Models\NotificationSetting;
use App\Models\User;
use App\Support\Notifications\NotificationEvents;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNotificationSettingControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_index_renders_all_event_groups(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/notification-settings');

        $response->assertOk();
        $response->assertSee('رزرو نوبت');
        $response->assertSee('برداشت وجه متخصص');
        $response->assertSee('تنظیمات اطلاع‌رسانی');
    }

    public function test_update_persists_checked_and_unchecked_state_correctly(): void
    {
        $key = NotificationEvents::BOOKING_CREATED_CUSTOMER;
        $safeKey = str_replace('.', '__', $key);

        $response = $this->actingAs($this->admin)->post('/admin/notification-settings', [
            'sms' => [$safeKey => '1'],
            'database' => [$safeKey => '1'],
            // telegram intentionally omitted (unchecked)
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('notification_settings', [
            'event_key' => $key,
            'sms_enabled' => 1,
            'database_enabled' => 1,
            'telegram_enabled' => 0,
        ]);
    }

    public function test_update_turns_off_a_channel_that_was_previously_on(): void
    {
        NotificationSetting::create([
            'event_key' => NotificationEvents::BOOKING_CONFIRMED_CUSTOMER,
            'sms_enabled' => true,
            'database_enabled' => true,
            'telegram_enabled' => false,
        ]);

        // The form is submitted without any key for this event => meaning the admin has unchecked all of her checkboxes.
        // The form is submitted without any key for this event => meaning the admin has unchecked all of his checkboxes.
        $this->actingAs($this->admin)->post('/admin/notification-settings', []);

        $this->assertDatabaseHas('notification_settings', [
            'event_key' => NotificationEvents::BOOKING_CONFIRMED_CUSTOMER,
            'sms_enabled' => 0,
            'database_enabled' => 0,
        ]);
    }

    public function test_non_admin_cannot_access_notification_settings(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin/notification-settings')->assertForbidden();
        $this->actingAs($user)->post('/admin/notification-settings', [])->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/notification-settings')->assertRedirect('/login');
    }
}
