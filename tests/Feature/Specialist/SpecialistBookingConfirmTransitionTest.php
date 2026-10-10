<?php

namespace Tests\Feature\Specialist;

use App\Models\Booking;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * ⭐ رگرسیون (۲۰۲۶-۱۰-۱۰، بسته‌ی ۲ اپلیکیشن): «پذیرش نوبت» پنل متخصص فقط pending → confirmed.
 * probe قبل از رفع: cancelled، pending_payment و completed هر سه confirmed می‌شدند.
 */
class SpecialistBookingConfirmTransitionTest extends TestCase
{
    use RefreshDatabase;

    protected function bookingFor(Specialist $specialist, string $status, string $payment): Booking
    {
        return Booking::factory()->create([
            'specialist_id' => $specialist->id,
            'status' => $status,
            'payment_status' => $payment,
            'booking_time' => now()->addDay()->setTime(10, 0),
        ]);
    }

    public function test_pending_booking_is_confirmed(): void
    {
        $specialist = Specialist::factory()->create();
        $booking = $this->bookingFor($specialist, 'pending', 'paid');

        $this->actingAs(User::find($specialist->user_id))
            ->put(route('specialist.bookings.complete', $booking))
            ->assertSessionHas('success');

        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    #[DataProvider('nonPendingStatuses')]
    public function test_non_pending_booking_is_not_confirmed(string $status, string $payment): void
    {
        $specialist = Specialist::factory()->create();
        $booking = $this->bookingFor($specialist, $status, $payment);

        $this->actingAs(User::find($specialist->user_id))
            ->put(route('specialist.bookings.complete', $booking))
            ->assertSessionHas('error');

        $this->assertSame($status, $booking->fresh()->status);
    }

    public static function nonPendingStatuses(): array
    {
        return [
            'cancelled (refunded)' => ['cancelled', 'paid'],
            'customer still at the gateway' => ['pending_payment', 'unpaid'],
            'completed' => ['completed', 'paid'],
        ];
    }
}
