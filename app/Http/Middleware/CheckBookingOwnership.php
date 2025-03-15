<?php

namespace App\Http\Middleware;

use App\Models\Booking;
use Closure;
use Illuminate\Http\Request;

class CheckBookingOwnership
{
    public function handle(Request $request, Closure $next)
    {
        $bookingId = $request->route('booking');

        if (is_object($bookingId)) {
            $booking = $bookingId;
        } else {
            $booking = Booking::findOrFail($bookingId);
        }

        if ($booking->user_id !== auth()->id()) {
            abort(403, 'شما دسترسی به این نوبت را ندارید.');
        }

        $request->route()->setParameter('booking', $booking);

        return $next($request);
    }
}
