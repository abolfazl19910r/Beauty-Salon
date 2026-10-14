<?php

namespace App\Http\Controllers\Admin\Booking;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\Booking\BookingNoShowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** «نیامد» از پنل مدیر (بسته‌ی ب، ادامه ۷) — پیش‌پرداخت مال سالن، جلسه‌ی دوره می‌سوزد */
class AdminBookingNoShowController extends Controller
{
    public function __invoke(Request $request, Booking $booking, BookingNoShowService $noShow): RedirectResponse
    {
        return $noShow->mark($booking, $request->user())
            ? back()->with('success', 'نوبت «نیامد» ثبت شد؛ پیش‌پرداخت برنمی‌گردد.')
            : back()->with('error', 'فقط نوبت فعالی که ساعتش گذشته «نیامد» می‌شود.');
    }
}
