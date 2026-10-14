<?php

namespace App\Services\Booking;

use App\Models\Booking;
use App\Models\User;
use App\Services\Courses\CourseService;
use Illuminate\Support\Facades\DB;

/**
 * «نیامد» (no-show؛ کارهای باز بسته‌ی ب، ادامه ۷ — تصمیم ابوالفضل ۲۰۲۶-۱۰-۱۴):
 * - فقط نوبت فعال (pending/confirmed) که ساعت شروعش گذشته؛ مدیر سالن یا متخصص همان نوبت (وب و اپ همکار).
 * - پیش‌پرداخت مال سالن می‌ماند: هیچ برگشت پولی نیست و درآمد پیش‌پرداخت متخصص (که لحظه‌ی پرداخت ثبت شده) دست نمی‌خورد.
 * - جلسه‌ی دوره‌ی درمانی می‌سوزد (CourseService::onBookingNoShow)، بدون درآمد جلسه برای متخصص.
 * - امتیاز باشگاه و پیامک «نظر بدهید» نمی‌گیرد (فقط completed می‌گیرد)؛ پیامکی هم به مشتری نمی‌رود.
 * یک‌بارمصرف: تغییر وضعیت با شرط وضعیت قبلی؛ اجرای هم‌زمان دوم اثری ندارد.
 */
class BookingNoShowService
{
    public function __construct(private readonly CourseService $courses) {}

    /** @return bool true = همین الان «نیامد» شد */
    public function mark(Booking $booking, User $by): bool
    {
        if (! $booking->canBeMarkedNoShow()) {
            return false;
        }

        $changed = DB::transaction(fn () => Booking::withoutGlobalScopes()->whereKey($booking->id)
            ->whereIn('status', ['pending', 'confirmed'])->where('booking_time', '<=', now())
            ->toBase()->update(['status' => 'no_show', 'no_show_at' => now(), 'no_show_by' => $by->id, 'updated_at' => now()]) === 1);

        if (! $changed) {
            return false;
        }

        $booking->refresh();
        $this->courses->onBookingNoShow($booking);
        activity()->performedOn($booking)->causedBy($by)->log('booking_no_show');

        return true;
    }
}
