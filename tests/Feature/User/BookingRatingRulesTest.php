<?php

namespace Tests\Feature\User;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * امتیاز فقط به نوبت انجام‌شده و فقط یک بار (۲۰۲۶-۱۰-۱۱). باگ: هر ارسال فرم امتیاز (هر نوبتی، هر چند بار)
 * ۱۰ امتیاز باشگاه مشتریان اضافه می‌کرد.
 */
class BookingRatingRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_rating_the_same_booking_twice_awards_points_only_once(): void
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $user->id, 'status' => 'completed', 'rating' => null]);

        $this->actingAs($user)->post(route('bookings.rate', $booking), ['rating' => 5])->assertSessionHas('success');
        $this->actingAs($user)->post(route('bookings.rate', $booking), ['rating' => 1])->assertSessionHas('error');
        $this->actingAs($user)->post(route('bookings.rate', $booking), ['rating' => 1]);

        $this->assertSame(10, $user->fresh()->getTotalLoyaltyPoints());
        $this->assertSame(5, $booking->fresh()->rating);
    }

    public function test_a_booking_that_is_not_completed_cannot_be_rated(): void
    {
        $user = User::factory()->create();

        foreach (['pending', 'confirmed', 'cancelled', 'pending_payment'] as $status) {
            $booking = Booking::factory()->create(['user_id' => $user->id, 'status' => $status, 'rating' => null]);
            $this->actingAs($user)->post(route('bookings.rate', $booking), ['rating' => 5])->assertSessionHas('error');
            $this->assertNull($booking->fresh()->rating, $status);
        }

        $this->assertSame(0, $user->fresh()->getTotalLoyaltyPoints());
    }
}
