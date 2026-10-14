<?php

namespace App\Http\Controllers\Specialist\Booking;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\Booking\BookingNoShowService;
use App\Traits\ResolvesSpecialist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** «نیامد» از پنل متخصص (بسته‌ی ب، ادامه ۷) — فقط نوبت خود متخصص */
class SpecialistBookingNoShowController extends Controller
{
    use ResolvesSpecialist;

    public function __invoke(Request $request, Booking $booking, BookingNoShowService $noShow): RedirectResponse
    {
        $specialist = $this->resolveSpecialist();
        abort_unless($specialist && (int) $booking->specialist_id === (int) $specialist->id, 404);

        return $noShow->mark($booking, $request->user())
            ? back()->with('success', 'نوبت «نیامد» ثبت شد.')
            : back()->with('error', 'فقط نوبت فعالی که ساعتش گذشته «نیامد» می‌شود.');
    }
}
