<?php

namespace Tests\Feature\Notification;

use App\Models\Booking;
use App\Models\NotificationSetting;
use App\Models\Specialist;
use App\Models\User;
use App\Notifications\Booking\BookingRescheduledNotification;
use App\Support\Notifications\NotificationEvents;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * اطلاع تغییر زمان به متخصص با کلید تنظیمات خودش («تغییر زمان نوبت — اطلاع به متخصص»)، جدا از کلید مشتری.
 */
class RescheduleSpecialistSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_specialist_and_the_customer_have_separate_switches(): void
    {
        $specialist = Specialist::factory()->create();
        $customer = User::factory()->create();
        $booking = Booking::factory()->create(['specialist_id' => $specialist->id, 'user_id' => $customer->id]);
        NotificationSetting::create(['event_key' => NotificationEvents::BOOKING_RESCHEDULED_SPECIALIST, 'sms_enabled' => false, 'database_enabled' => true, 'telegram_enabled' => false]);

        $notification = new BookingRescheduledNotification($booking, now()->subDay());

        $this->assertNotContains('sms', $notification->via($specialist));
        $this->assertContains('sms', $notification->via($customer));
        $this->assertArrayHasKey(NotificationEvents::BOOKING_RESCHEDULED_SPECIALIST, array_flip(NotificationEvents::allKeys()));
    }
}
