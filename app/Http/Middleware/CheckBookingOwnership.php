<?php

namespace App\Http\Middleware;

use App\Models\Booking;
use Closure;
use Illuminate\Http\Request;

class CheckBookingOwnership
{
    public function handle(Request $request, Closure $next)
    {
        $booking = $request->route('booking');

        if ($booking->user_id !== auth()->id()) {
            abort(403, 'شما دسترسی به این نوبت را ندارید.');
        }

        return $next($request);
    }
}
