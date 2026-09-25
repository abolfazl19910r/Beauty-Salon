<?php

namespace Tests\Feature\Notification;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Specialist;
use App\Models\SpecialistSchedule;
use App\Models\User;
use App\Models\UserNotification;
use App\Notifications\Review\NewReviewNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * اعلان‌هایی که به خودِ مدل Specialist فرستاده می‌شن (نوبت جدید، تغییر زمان، لغو، نظر، برداشت) ستون
 * user_id جدول user_notifications رو با شناسه‌ی متخصص پر می‌کردن، نه کاربرِ متخصص. روی MySQL/MariaDB وقتی
 * کاربر هم‌شماره نبود، کلید خارجی رد می‌شد (تغییر زمان نوبت ۵۰۰، امتیاز وفاداری بعد از نظر داده نمی‌شد)، و وقتی
 * بود، اعلان به یک کاربر بی‌ربط نسبت داده می‌شد. روی SQLite شمارنده‌ی شناسه‌ها با rollback هر تست برمی‌گرده،
 * پس شناسه‌ی متخصص همیشه با یک کاربر موجود جور درمی‌اومد و سوییت هیچ‌وقت نمی‌دید. اینجا شناسه‌ی متخصص عمداً
 * از همه‌ی کاربرها دوره.
 */
class SpecialistNotificationOwnerTest extends TestCase
{
    use RefreshDatabase;

    private const FAR_SPECIALIST_ID = 900001;

    private function farSpecialist(array $attributes = []): Specialist
    {
        return Specialist::factory()->create(array_merge(['id' => self::FAR_SPECIALIST_ID], $attributes));
    }

    public function test_a_notification_to_a_specialist_belongs_to_the_specialists_own_user(): void
    {
        $specialist = $this->farSpecialist();
        $booking = Booking::factory()->create(['specialist_id' => $specialist->id, 'status' => 'completed']);

        $specialist->notify(new NewReviewNotification($booking));

        $row = UserNotification::where('notifiable_type', Specialist::class)->where('notifiable_id', $specialist->id)->sole();
        $this->assertSame($specialist->user_id, $row->user_id);
    }

    public function test_a_specialist_without_a_linked_user_still_receives_the_notification(): void
    {
        $specialist = $this->farSpecialist(['user_id' => null]);
        $booking = Booking::factory()->create(['specialist_id' => $specialist->id, 'status' => 'completed']);

        $specialist->notify(new NewReviewNotification($booking));

        $row = $specialist->notifications()->sole();
        $this->assertNull($row->user_id);
    }

    public function test_a_specialist_notification_is_never_attributed_to_an_unrelated_user_with_the_same_id(): void
    {
        $customer = User::factory()->create();
        $specialistUser = User::factory()->create();
        $specialist = Specialist::factory()->create(['id' => $customer->id, 'user_id' => $specialistUser->id]);
        $booking = Booking::factory()->create(['specialist_id' => $specialist->id, 'status' => 'completed']);

        $specialist->notify(new NewReviewNotification($booking));

        $this->assertSame(0, UserNotification::where('user_id', $customer->id)->count());
        $this->assertSame(1, UserNotification::where('user_id', $specialistUser->id)->count());
    }

    public function test_a_customer_can_reschedule_when_no_user_shares_the_specialists_id(): void
    {
        $customer = User::factory()->create();
        $service = BeautyService::factory()->create(['duration' => 30]);
        $target = now()->addDays(3)->setTime(11, 0);
        $specialist = $this->farSpecialist(['auto_confirm_bookings' => false]);
        SpecialistSchedule::factory()->create([
            'specialist_id' => $specialist->id,
            'day_of_week' => $target->dayOfWeek,
            'start_time' => '08:00',
            'end_time' => '20:00',
            'is_active' => true,
        ]);
        $booking = Booking::factory()->create([
            'user_id' => $customer->id,
            'specialist_id' => $specialist->id,
            'service_id' => $service->id,
            'status' => 'confirmed',
            'booking_time' => now()->addDays(5),
        ]);

        $response = $this->actingAs($customer)->putJson(route('bookings.update-reschedule', ['booking' => $booking->id]), [
            'booking_time' => $target->format('Y-m-d H:i:s'),
        ]);

        $response->assertOk()->assertJson(['success' => true, 'redirect' => route('bookings.show', $booking)]);
        $this->assertSame($target->format('Y-m-d H:i:s'), $booking->fresh()->booking_time->format('Y-m-d H:i:s'));
        $this->assertSame(1, $specialist->notifications()->count());
    }

    public function test_rating_a_booking_awards_loyalty_points_when_no_user_shares_the_specialists_id(): void
    {
        $customer = User::factory()->create();
        $specialist = $this->farSpecialist();
        $booking = Booking::factory()->create([
            'user_id' => $customer->id,
            'specialist_id' => $specialist->id,
            'status' => 'completed',
        ]);
        $before = $customer->getTotalLoyaltyPoints();

        $response = $this->actingAs($customer)->post(route('bookings.rate', ['booking' => $booking->id]), [
            'rating' => 5,
            'review' => 'عالی بود',
        ]);

        $response->assertSessionHas('success');
        $this->assertSame($before + 10, $customer->fresh()->getTotalLoyaltyPoints());
        $this->assertSame(1, $specialist->notifications()->count());
    }
}
