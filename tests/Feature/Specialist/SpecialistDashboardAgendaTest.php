<?php

namespace Tests\Feature\Specialist;

use App\Models\Booking;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ تصمیم ۲۰۲۶-۱۰-۱۰: «برنامه‌ی امروز» و «۷ روز آینده» داشبورد متخصص همه‌ی نوبت‌های فعال را نشان می‌دهند
 * (پرداخت‌شده یا نه)؛ لغوشده و «در درگاه» نه. قبلاً فقط پرداخت‌شده‌ها، حتی لغوشده.
 */
class SpecialistDashboardAgendaTest extends TestCase
{
    use RefreshDatabase;

    public function test_today_and_upcoming_show_active_bookings_regardless_of_payment(): void
    {
        $this->travelTo(now()->setTime(8, 0));
        $specialist = Specialist::factory()->create();

        $make = fn (string $status, string $payment, int $hour, int $day = 0) => Booking::factory()->create([
            'specialist_id' => $specialist->id,
            'status' => $status,
            'payment_status' => $payment,
            'booking_time' => now()->addDays($day)->setTime($hour, 0),
        ]);

        $manualUnpaid = $make('confirmed', 'unpaid', 10);
        $paidPending = $make('pending', 'paid', 11);
        $cancelledPaid = $make('cancelled', 'paid', 12);
        $atGateway = $make('pending_payment', 'unpaid', 13);
        $upcomingManual = $make('confirmed', 'unpaid', 10, 2);
        $upcomingCancelled = $make('cancelled', 'paid', 11, 2);

        $response = $this->actingAs(User::find($specialist->user_id))->get(route('specialist.my-dashboard'));

        $response->assertOk();
        $today = $response->viewData('todaySchedule')->pluck('id')->all();
        $this->assertSame([$manualUnpaid->id, $paidPending->id], $today);
        $this->assertSame(2, $response->viewData('todayBookingsCount'));

        $upcoming = $response->viewData('upcomingBookings')->pluck('id');
        $this->assertTrue($upcoming->contains($upcomingManual->id));
        $this->assertFalse($upcoming->contains($upcomingCancelled->id));
        $this->assertFalse($upcoming->contains($atGateway->id));
        $this->assertFalse(collect($today)->contains($cancelledPaid->id));
    }
}
