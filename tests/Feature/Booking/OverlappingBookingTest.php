<?php

namespace Tests\Feature\Booking;

use App\Exceptions\BookingNotAvailableException;
use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Specialist;
use App\Models\SpecialistSchedule;
use App\Models\User;
use App\Services\Admin\Booking\AdminBookingService;
use App\Services\Booking\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * نوبت‌های هم‌پوشان یک متخصص (۲۰۲۶-۱۰-۰۱): چک زمان آزاد فقط یک پنجره‌ی ۳۰ دقیقه‌ای از ساعت شروع را می‌دید
 * (getAvailableSlots بدون مدت خدمت)، پس خدمت بلندتری که به نوبت بعدی، استراحت یا پایان ساعت کاری می‌رسید ثبت می‌شد.
 * قید یکتای active_slot فقط شروع یکسان را می‌گیرد. هر چهار مسیر: نوبت مشتری، نوبت دستی مدیر، تغییر زمان مشتری،
 * ویرایش مدیر.
 */
class OverlappingBookingTest extends TestCase
{
    use RefreshDatabase;

    private Specialist $specialist;

    private BeautyService $short;

    private BeautyService $long;

    private Carbon $day;

    protected function setUp(): void
    {
        parent::setUp();
        $this->specialist = Specialist::factory()->create();
        $this->short = BeautyService::factory()->create(['duration' => 30, 'price' => 100000]);
        $this->long = BeautyService::factory()->create(['duration' => 60, 'price' => 100000]);
        $this->day = now()->addDays(2)->startOfDay();
        SpecialistSchedule::factory()->create([
            'specialist_id' => $this->specialist->id, 'day_of_week' => $this->day->dayOfWeek,
            'start_time' => '08:00', 'end_time' => '20:00', 'break_start' => '13:00', 'break_end' => '14:00', 'is_active' => true,
        ]);
    }

    private function at(string $time): string
    {
        return $this->day->format('Y-m-d').' '.$time.':00';
    }

    private function existingAt(string $time, ?BeautyService $service = null): Booking
    {
        return Booking::factory()->create([
            'specialist_id' => $this->specialist->id, 'service_id' => ($service ?? $this->short)->id,
            'booking_time' => $this->at($time), 'status' => 'confirmed',
        ]);
    }

    private function assertRejected(callable $attempt, string $label): void
    {
        $count = Booking::count();
        try {
            $attempt();
            $this->fail("{$label}: نوبت هم‌پوشان پذیرفته شد");
        } catch (BookingNotAvailableException) {
            $this->assertSame($count, Booking::count(), $label);
        }
    }

    public function test_a_customer_cannot_book_a_long_service_into_the_next_booking(): void
    {
        $this->existingAt('10:30');
        $customer = User::factory()->create();

        $this->assertRejected(fn () => app(BookingService::class)
            ->createBooking($customer->id, $this->long->id, $this->specialist->id, $this->at('10:00')), 'مشتری');
    }

    public function test_a_long_service_cannot_run_into_the_break_or_past_closing_time(): void
    {
        $customer = User::factory()->create();

        $this->assertRejected(fn () => app(BookingService::class)
            ->createBooking($customer->id, $this->long->id, $this->specialist->id, $this->at('12:30')), 'استراحت');
        $this->assertRejected(fn () => app(BookingService::class)
            ->createBooking($customer->id, $this->long->id, $this->specialist->id, $this->at('19:30')), 'پایان ساعت کاری');
    }

    public function test_an_admin_manual_booking_cannot_overlap_the_next_booking(): void
    {
        $this->existingAt('10:30');

        $this->assertRejected(fn () => app(BookingService::class)->createManualBooking([
            'service_id' => $this->long->id, 'specialist_id' => $this->specialist->id, 'user_id' => User::factory()->create()->id,
            'booking_time' => $this->at('10:00'), 'status' => 'confirmed', 'payment_status' => 'unpaid', 'source' => 'phone',
        ]), 'مدیر');
    }

    public function test_a_customer_reschedule_cannot_overlap_another_booking(): void
    {
        $this->existingAt('10:30');
        $customer = User::factory()->create();
        $mine = $this->existingAt('16:00', $this->long);
        $mine->update(['user_id' => $customer->id]);

        $this->actingAs($customer)->put(route('bookings.update-reschedule', $mine), ['booking_time' => $this->at('10:00')])
            ->assertSessionHas('error');

        $this->assertSame($this->at('16:00'), $mine->fresh()->booking_time->format('Y-m-d H:i:s'));
    }

    public function test_an_admin_edit_that_lengthens_the_service_cannot_overlap_the_next_booking(): void
    {
        $this->existingAt('10:30');
        $booking = $this->existingAt('10:00');

        $this->assertRejected(fn () => app(AdminBookingService::class)->updateFull($booking, ['service_id' => $this->long->id]), 'ویرایش مدیر');
        $this->assertSame($this->short->id, $booking->fresh()->service_id);
    }

    public function test_back_to_back_bookings_are_still_allowed(): void
    {
        $this->existingAt('11:00');
        $customer = User::factory()->create();

        $booking = app(BookingService::class)->createBooking($customer->id, $this->long->id, $this->specialist->id, $this->at('10:00'));

        $this->assertSame($this->at('10:00'), $booking->booking_time->format('Y-m-d H:i:s'));
    }
}
