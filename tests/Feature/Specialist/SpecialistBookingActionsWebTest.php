<?php

namespace Tests\Feature\Specialist;

use App\Events\Booking\BookingCancelled;
use App\Events\Booking\Completed\BookingCompleted;
use App\Models\Booking;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * رفتار وب لغو و «انجام شد» متخصص — پیش از انتقال منطق به SpecialistBookingActions (بسته‌ی ۲) روی کنترلر قدیمی
 * اجرا و سبز شد؛ بعد از انتقال هم باید همین بماند.
 */
class SpecialistBookingActionsWebTest extends TestCase
{
    use RefreshDatabase;

    protected Specialist $specialist;

    protected User $specialistUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->specialist = Specialist::factory()->create();
        $this->specialistUser = User::find($this->specialist->user_id);
    }

    protected int $hour = 9;

    protected function booking(string $status, ?Specialist $specialist = null): Booking
    {
        // هر نوبت ساعت خودش (ایندکس یکتای ساعت فعال متخصص)
        return Booking::factory()->create([
            'specialist_id' => ($specialist ?? $this->specialist)->id,
            'status' => $status,
            'payment_status' => 'paid',
            'booking_time' => now()->addDays(3)->setTime($this->hour++, 0),
        ]);
    }

    public function test_cancel_records_reason_and_canceller_and_fires_the_event(): void
    {
        Event::fake([BookingCancelled::class]);
        $booking = $this->booking('confirmed');

        $this->actingAs($this->specialistUser)
            ->put(route('specialist.bookings.cancel', $booking), ['cancel_reason' => 'بیماری'])
            ->assertSessionHas('success');

        $booking->refresh();
        $this->assertSame('cancelled', $booking->status);
        $this->assertSame('specialist', $booking->cancelled_by);
        $this->assertSame('بیماری', $booking->cancellation_reason);
        $this->assertNotNull($booking->cancelled_at);
        Event::assertDispatched(BookingCancelled::class, fn ($e) => $e->booking->id === $booking->id && $e->cancelledBy === 'specialist');
    }

    public function test_cancel_without_reason_uses_the_default_text(): void
    {
        $booking = $this->booking('pending');

        $this->actingAs($this->specialistUser)->put(route('specialist.bookings.cancel', $booking));

        $this->assertSame('دلیل مشخص نشده', $booking->fresh()->cancellation_reason);
    }

    public function test_completed_booking_cannot_be_cancelled_and_cancelled_is_a_no_op(): void
    {
        Event::fake([BookingCancelled::class]);
        $completed = $this->booking('completed');
        $cancelled = $this->booking('cancelled');

        $this->actingAs($this->specialistUser)->put(route('specialist.bookings.cancel', $completed))->assertSessionHas('error');
        $this->actingAs($this->specialistUser)->put(route('specialist.bookings.cancel', $cancelled))->assertSessionHas('info');

        $this->assertSame('completed', $completed->fresh()->status);
        Event::assertNotDispatched(BookingCancelled::class);
    }

    public function test_mark_completed_only_from_confirmed(): void
    {
        Event::fake([BookingCompleted::class]);
        $confirmed = $this->booking('confirmed');
        $pending = $this->booking('pending');

        $this->actingAs($this->specialistUser)->put(route('specialist.bookings.mark-completed', $confirmed))->assertSessionHas('success');
        $this->actingAs($this->specialistUser)->put(route('specialist.bookings.mark-completed', $pending))->assertSessionHas('error');

        $this->assertSame('completed', $confirmed->fresh()->status);
        $this->assertSame('pending', $pending->fresh()->status);
        Event::assertDispatchedTimes(BookingCompleted::class, 1);
    }

    public function test_another_specialists_booking_is_forbidden(): void
    {
        $other = Specialist::factory()->create();
        $booking = $this->booking('pending', $other);

        foreach (['complete', 'mark-completed', 'cancel'] as $action) {
            $this->actingAs($this->specialistUser)->put(route('specialist.bookings.'.$action, $booking))->assertForbidden();
        }

        $this->assertSame('pending', $booking->fresh()->status);
    }
}
