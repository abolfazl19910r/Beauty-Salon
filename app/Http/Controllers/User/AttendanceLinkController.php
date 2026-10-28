<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\Booking\AttendanceCheckService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * لینک پیامکی «حضورم را تأیید می‌کنم» (بسته‌ی 5ه، تصمیم ۱۶-۳): بی ورود؛ فقط هش توکن در جدول، فقط در سالن خودش (scope سالن). صفحه
 * با دکمه (POST) تا پیش‌نمایش لینک در پیام‌رسان‌ها خودش تأیید نکند.
 */
class AttendanceLinkController extends Controller
{
    public function __construct(private readonly AttendanceCheckService $attendance) {}

    public function show(string $token): View
    {
        $booking = $this->attendance->findByToken($token);
        abort_unless($booking, 404);

        return view('bookings.attendance', ['booking' => $booking, 'token' => $token]);
    }

    public function confirm(string $token): RedirectResponse
    {
        $booking = $this->attendance->findByToken($token);
        abort_unless($booking, 404);

        if ($this->attendance->confirm($booking) || $booking->attendance_confirmed_at) {
            return back()->with('success', 'حضور شما تأیید شد. منتظر دیدارتان هستیم.');
        }

        return back()->with('error', 'این نوبت دیگر فعال نیست و تأیید آن ممکن نیست؛ با سالن تماس بگیرید.');
    }
}
