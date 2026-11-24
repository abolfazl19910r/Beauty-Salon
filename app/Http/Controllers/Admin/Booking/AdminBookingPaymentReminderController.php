<?php

namespace App\Http\Controllers\Admin\Booking;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Notifications\Booking\BookingPaymentReminderNotification;
use App\Services\Links\ShortLinkService;
use App\Support\CurrentSalon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * «یادآوری پرداخت» برای نوبت آینده‌ی منتظر پیش‌پرداخت (بسته‌ی ۲ ظاهری، ۲۰۲۶-۱۱-۲۵). برای هر نوبت در هر ۳ ساعت یک بار تا دو کلیک
 * دو پیامک نشود. پیامک از سهمیه‌ی سالن کم می‌شود.
 */
class AdminBookingPaymentReminderController extends Controller
{
    public const COOLDOWN_HOURS = 3;

    public function __invoke(Request $request, Booking $booking, ShortLinkService $links): RedirectResponse
    {
        // SubstituteBindings پیش از salon.active اجرا می‌شود؛ مالکیت را صریح بسنج (learnings: binding gap)
        abort_unless($booking->salon_id === app(CurrentSalon::class)->id(), 404);
        abort_unless($request->user()?->hasPermission('edit-bookings'), 403);

        if ($booking->status !== 'pending_payment' || ! $booking->booking_time?->isFuture() || ! $booking->user) {
            return back()->with('error', 'یادآوری پرداخت فقط برای نوبت آینده‌ای است که منتظر پیش‌پرداخت است.');
        }

        if (! Cache::add('booking-payment-reminder:'.$booking->id, true, now()->addHours(self::COOLDOWN_HOURS))) {
            return back()->with('error', 'برای این نوبت تازه یادآوری فرستاده شده؛ تا ۳ ساعت دیگر دوباره نمی‌شود.');
        }

        $link = $links->shorten(
            route('payment.show', ['salon_slug' => app(CurrentSalon::class)->get()?->slug, 'booking' => $booking->id]),
            $booking->booking_time->copy()
        );
        $booking->user->notify(new BookingPaymentReminderNotification($booking, $link));

        return back()->with('success', 'پیامک یادآوری پیش‌پرداخت برای '.$booking->user->name.' فرستاده شد.');
    }
}
