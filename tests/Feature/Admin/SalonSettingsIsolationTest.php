<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Notifications\Booking\AdminNewBookingNotification;
use App\Notifications\Booking\BookingNotification;
use App\Services\Notification\NotificationSettingService;
use App\Support\CurrentSalon;
use App\Support\Notifications\NotificationEvents;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ تصمیم ۲۰۲۶-۰۹-۲۷: تنظیمات اطلاع‌رسانی و تنظیمات امنیتی مال هر سالن جداست. هر دو قبلاً یک ردیف مشترک برای کل
 * پلتفرم بودند: خاموش‌کردن پیامک یک رویداد توسط یک سالن، اون پیامک رو برای مشتری‌های همه‌ی سالن‌ها قطع می‌کرد، و
 * «مدت اعتبار رمز» هم برای همه یکی بود.
 */
class SalonSettingsIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Salon $salonA;

    private Salon $salonB;

    private User $ownerA;

    private User $ownerB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->salonA = app(CurrentSalon::class)->get();
        $this->ownerA = User::factory()->create(['is_admin' => true, 'user_type' => 'staff', 'salon_id' => null]);
        $this->salonB = Salon::factory()->create(['slug' => 'other-settings']);
        app(CurrentSalon::class)->set($this->salonB);
        $this->ownerB = User::factory()->create(['is_admin' => true, 'user_type' => 'staff', 'salon_id' => null]);
        app(CurrentSalon::class)->set($this->salonA);
    }

    private function saveNotificationSettings(User $owner, array $checked): void
    {
        $payload = [];
        foreach ($checked as [$channel, $key]) {
            $payload[$channel][str_replace('.', '__', $key)] = '1';
        }
        $this->actingAs($owner)->post(route('admin.notification-settings.update'), $payload)->assertRedirect();
    }

    public function test_turning_a_channel_off_in_one_salon_does_not_affect_another(): void
    {
        $this->saveNotificationSettings($this->ownerA, []);

        $service = app(NotificationSettingService::class);
        $this->assertFalse($service->isEnabled(NotificationEvents::BOOKING_CONFIRMED_CUSTOMER, 'sms', $this->salonA->id));
        $this->assertTrue($service->isEnabled(NotificationEvents::BOOKING_CONFIRMED_CUSTOMER, 'sms', $this->salonB->id));

        $page = $this->actingAs($this->ownerB)->get(route('admin.notification-settings.index'))->assertOk();
        $this->assertTrue((bool) $page->viewData('settings')[NotificationEvents::BOOKING_CONFIRMED_CUSTOMER]->sms_enabled);
    }

    public function test_a_queued_notification_follows_its_recipients_salon_settings(): void
    {
        $this->saveNotificationSettings($this->ownerA, []);
        $booking = Booking::factory()->create();
        app(CurrentSalon::class)->clear();

        $this->assertSame([], (new AdminNewBookingNotification($booking))->via($this->ownerA));
        $this->assertSame(['database'], (new AdminNewBookingNotification($booking))->via($this->ownerB));

        app(CurrentSalon::class)->set($this->salonB);
        $specialistB = Specialist::factory()->create();
        app(CurrentSalon::class)->set($this->salonA);
        $specialistA = Specialist::factory()->create();
        app(CurrentSalon::class)->clear();

        $this->assertSame([], (new BookingNotification($booking))->via($specialistA));
        $this->assertSame(['database', 'sms'], (new BookingNotification($booking))->via($specialistB));
    }

    public function test_password_expiry_is_set_per_salon(): void
    {
        $this->actingAs($this->ownerA)->post(route('admin.security.settings.update'), ['password_expiry_days' => 30])->assertRedirect();

        $this->assertSame(30, $this->actingAs($this->ownerA)->get(route('admin.security.settings'))->viewData('settings')->password_expiry_days);
        $this->assertSame(90, $this->actingAs($this->ownerB)->get(route('admin.security.settings'))->viewData('settings')->password_expiry_days);
    }
}
