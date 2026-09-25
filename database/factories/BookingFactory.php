<?php

namespace Database\Factories;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $service = BeautyService::inRandomOrder()->first() ?? BeautyService::factory()->create();
        $specialist = Specialist::inRandomOrder()->first() ?? Specialist::factory()->create();
        $user = User::inRandomOrder()->first() ?? User::factory()->create();
        $status = fake()->randomElement(['pending', 'confirmed', 'cancelled']);

        $bookingTime = $this->drawNonCollidingBookingTime($specialist->id, $status);

        return [
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'user_id' => $user->id,
            'booking_time' => $bookingTime,
            'status' => $status,
            'prepayment_amount' => 50000,
            'payment_status' => 'unpaid',
            'rating' => fake()->optional(0.3)->numberBetween(1, 5),
            'review' => fake()->optional(0.3)->sentence(),
            'reminder_sent' => false,
            'discount_code' => null,
            'discount_amount' => null,
            'cancelled_by' => null,
            'cancellation_reason' => null,
            'cancelled_at' => null,
        ];
    }

    /**
     * ⭐ fix/admin-booking-slot-conflict added a DB-level unique index (active_slot_key) so no
     * two non-cancelled bookings can share the same (specialist_id, booking_time). This factory
     * used to draw both values completely at random, with no awareness of what's already taken —
     * fine on its own in most single-booking tests, but any seeder or test that creates several
     * bookings in a loop (only a handful of specialists exist, so the pool of distinct slots is
     * small) could randomly collide and crash with a real UniqueConstraintViolationException.
     * Confirmed by actually running `php artisan migrate:fresh --seed` repeatedly: real,
     * intermittent crashes in BookingSeeder/LoyaltySimulationSeeder before this fix. Explicit
     * overrides passed to create(['specialist_id' => ..., 'booking_time' => ...]) always replace
     * whatever this draws anyway, so tests that deliberately construct a collision (see
     * AdminBookingSlotConflictTest) are unaffected.
     */
    private function drawNonCollidingBookingTime(int $specialistId, string $status): \DateTime
    {
        $bookingTime = null;

        for ($attempt = 0; $attempt < 20; $attempt++) {
            // ⭐ (۲۰۲۶-۰۹-۲۶) بازه از now() کربن (نه رشته‌ی '+2 days' که Faker با ساعت واقعی سیستم حساب می‌کنه): factory با
            // travelTo تست‌ها هماهنگه. و از +۲ روز، نه +۱: ساعت بعداً ۹ تا ۱۷ گذاشته می‌شه و «فردا» می‌تونست کمتر از ۲۴ ساعت
            // باشه — داخل بازه‌ی جریمه‌ی لغو مشتری؛ ریشه‌ی تست ناپایدار BookingServiceTest (BookingFactoryTimeTest).
            $bookingTime = fake()->dateTimeBetween(now()->addDays(2), now()->addMonths(2));
            $bookingTime->setTime(fake()->numberBetween(9, 17), 0, 0);

            if ($status === 'cancelled') {
                break;
            }

            $collides = Booking::where('specialist_id', $specialistId)
                ->where('booking_time', $bookingTime)
                ->where('status', '!=', 'cancelled')
                ->exists();

            if (! $collides) {
                break;
            }
        }

        return $bookingTime;
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
        ]);
    }

    public function cancelledByUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_by' => 'customer',
            'cancellation_reason' => fake()->randomElement(['تغییر برنامه', 'لغو اضطراری']),
            'cancelled_at' => now(),
        ]);
    }
}
