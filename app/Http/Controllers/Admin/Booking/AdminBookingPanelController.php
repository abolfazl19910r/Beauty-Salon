<?php

namespace App\Http\Controllers\Admin\Booking;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Support\CurrentSalon;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * پنل جزئیات نوبت در پوسته‌ی «میز پذیرش» (بسته‌ی ۲ب): HTML سمت سرور که بی بارگذاری دوباره‌ی صفحه کنار فهرست نوبت‌ها می‌نشیند.
 * وضعیت، مشتری، خدمت، کارشناس، زمان، پرداخت، دکمه‌های کار و سابقه‌ی کوتاه مشتری در همین سالن.
 */
class AdminBookingPanelController extends Controller
{
    public function __invoke(Request $request, Booking $booking): View
    {
        // SubstituteBindings پیش از salon.active اجرا می‌شود؛ مالکیت را صریح بسنج (learnings: binding gap)
        abort_unless($booking->salon_id === app(CurrentSalon::class)->id(), 404);
        abort_unless($request->user()?->hasPermission('view-bookings'), 403);

        $booking->load(['service', 'user', 'specialist']);

        $history = ['completed' => 0, 'no_show' => 0, 'last' => null];
        if ($booking->user_id) {
            $past = Booking::query()->where('user_id', $booking->user_id)->whereKeyNot($booking->id);
            $history['completed'] = (clone $past)->where('status', 'completed')->count();
            $history['no_show'] = (clone $past)->where('status', 'no_show')->count();
            $history['last'] = (clone $past)->where('status', 'completed')->with('service')->latest('booking_time')->first();
        }

        return view('admin.bookings.partials.desk-panel', compact('booking', 'history'));
    }
}
