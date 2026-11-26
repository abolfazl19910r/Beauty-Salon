<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Response;

/**
 * «افزودن به تقویم» (بسته‌ی ۴ ظاهری، ۲۰۲۶-۱۱-۲۵): فایل ‎.ics یک نوبت مشتری (iCalendar، زمان به UTC). مالکیت نوبت را
 * middleware ‏check.booking.ownership و policy ‏view می‌سنجند.
 */
class BookingCalendarController extends Controller
{
    public function __invoke(Booking $booking): Response
    {
        $this->authorize('view', $booking);

        return self::ics($booking);
    }

    /**
     * بسته‌ی ۵د: همان فایل برای «افزودن به تقویم» اپ مشتری — اپ نشانی امضاشده‌ی موقت (CustomerPresenter::calendarUrl) را در مرورگر
     * گوشی باز می‌کند و مرورگر نشست وب ندارد؛ امضا جای ورود است و شناسه‌ی نوبت را نمی‌شود عوض کرد. نوبت باید مال همین سالن باشد.
     */
    public function signed(Booking $booking): Response
    {
        abort_unless((int) $booking->salon_id === (int) app(\App\Support\CurrentSalon::class)->get()?->id, 404);

        return self::ics($booking);
    }

    public static function ics(Booking $booking): Response
    {
        $booking->loadMissing(['service', 'specialist']);

        $start = $booking->booking_time->copy()->utc();
        $end = $start->copy()->addMinutes((int) ($booking->service?->duration ?: 30));
        $salon = app(\App\Support\CurrentSalon::class)->get();
        $esc = fn (?string $v) => str_replace(['\\', ';', ',', "\r\n", "\n"], ['\\\\', '\;', '\,', '\n', '\n'], (string) $v);

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Mahru//Booking//FA',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:booking-'.$booking->id.'@'.(parse_url(config('app.url'), PHP_URL_HOST) ?: 'mahru'),
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.$start->format('Ymd\THis\Z'),
            'DTEND:'.$end->format('Ymd\THis\Z'),
            'SUMMARY:'.$esc(($booking->service?->name ?? 'نوبت').($salon ? ' — '.$salon->name : '')),
            'DESCRIPTION:'.$esc($booking->specialist ? 'با '.$booking->specialist->name : ''),
            'LOCATION:'.$esc($salon?->address),
            'BEGIN:VALARM',
            'TRIGGER:-PT2H',
            'ACTION:DISPLAY',
            'DESCRIPTION:'.$esc('یادآوری نوبت'),
            'END:VALARM',
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return response(implode("\r\n", $lines)."\r\n", 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="booking-'.$booking->id.'.ics"',
        ]);
    }
}
