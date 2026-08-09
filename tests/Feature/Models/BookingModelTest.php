<?php

namespace Tests\Feature\Models;

use App\Models\BeautyService;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_remaining_amount_is_service_price_minus_prepayment_and_discount(): void
    {
        $service = BeautyService::factory()->create(['price' => 250000]);
        $booking = Booking::factory()->create([
            'service_id' => $service->id,
            'prepayment_amount' => 75000,
            'discount_amount' => 7500,
        ]);

        $this->assertSame(167500.0, $booking->remaining_amount);
    }

    public function test_remaining_amount_never_goes_negative(): void
    {
        $service = BeautyService::factory()->create(['price' => 50000]);
        $booking = Booking::factory()->create([
            'service_id' => $service->id,
            'prepayment_amount' => 50000,
            'discount_amount' => 50000, // absurdly large combined discount/prepayment
        ]);

        $this->assertSame(0.0, $booking->remaining_amount);
    }

    public function test_remaining_amount_treats_null_discount_as_zero(): void
    {
        $service = BeautyService::factory()->create(['price' => 100000]);
        $booking = Booking::factory()->create([
            'service_id' => $service->id,
            'prepayment_amount' => 30000,
            'discount_amount' => null,
        ]);

        $this->assertSame(70000.0, $booking->remaining_amount);
    }

    public function test_can_be_rescheduled_when_pending_and_more_than_24_hours_away(): void
    {
        $booking = Booking::factory()->create([
            'status' => 'pending',
            'booking_time' => now()->addHours(48),
        ]);

        $this->assertTrue($booking->canBeRescheduled());
    }

    public function test_can_be_rescheduled_when_confirmed_and_more_than_24_hours_away(): void
    {
        $booking = Booking::factory()->create([
            'status' => 'confirmed',
            'booking_time' => now()->addHours(48),
        ]);

        $this->assertTrue($booking->canBeRescheduled());
    }

    public function test_cannot_be_rescheduled_within_24_hours(): void
    {
        $booking = Booking::factory()->create([
            'status' => 'confirmed',
            'booking_time' => now()->addHours(10),
        ]);

        $this->assertFalse($booking->canBeRescheduled());
    }

    public function test_cannot_be_rescheduled_when_cancelled(): void
    {
        $booking = Booking::factory()->create([
            'status' => 'cancelled',
            'booking_time' => now()->addHours(48),
        ]);

        $this->assertFalse($booking->canBeRescheduled());
    }

    public function test_cannot_be_rescheduled_when_completed(): void
    {
        $booking = Booking::factory()->create([
            'status' => 'completed',
            'booking_time' => now()->addHours(48),
        ]);

        $this->assertFalse($booking->canBeRescheduled());
    }
}
