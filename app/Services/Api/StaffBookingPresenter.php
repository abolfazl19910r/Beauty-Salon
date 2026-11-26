<?php

namespace App\Services\Api;

use App\Models\Booking;
use Morilog\Jalali\Jalalian;

/**
 * شکل نوبت در API اپ «ماهرو همکار» (بسته‌ی ۲). زمان‌ها ISO 8601 (برای منطق اپ) و شمسی (برای نمایش).
 * `actions` همان قاعده‌های SpecialistBookingActions است تا اپ دکمه‌ی بی‌اثر نشان ندهد.
 */
class StaffBookingPresenter
{
    /**
     * @param  array<int, string>|null  $noShowLabels  برچسب‌های بدقولی یک فهرست (NoShowRecord::labelsFor)؛ null = برای همین نوبت حساب شود
     */
    public static function present(Booking $booking, ?array $noShowLabels = null): array
    {
        $time = $booking->booking_time;

        return [
            'id' => $booking->id,
            'status' => $booking->status,
            'status_label' => \App\Support\Bookings\BookingLabels::status($booking->status, \App\Support\Bookings\BookingLabels::AUDIENCE_STAFF),
            'payment_status' => $booking->payment_status,
            'booking_time' => $time?->toIso8601String(),
            'booking_time_jalali' => $time ? Jalalian::fromCarbon($time)->format('Y/m/d H:i') : null,
            // بسته‌ی ۵د: برچسب‌های آماده با رقم فارسی («یکشنبه ۱۵ آذر»، «فردا جمعه ۶ آذر»، «۱۷:۳۰»)
            'date_label' => $time ? \App\Support\Fa::date($time) : null,
            'day_label' => $time ? self::dayLabel($time) : null,
            'time_label' => $time ? \App\Support\Fa::time($time) : null,
            // بسته‌ی ۵د: کار اصلی همان پنل وب (SpecialistDashboardService::primaryAction): confirm · complete · details
            'primary_action' => $time ? self::primaryKind($booking) : 'details',
            'duration_minutes' => $booking->duration_minutes ? (int) $booking->duration_minutes : ($booking->service?->duration !== null ? (int) $booking->service->duration : null),
            'service' => $booking->service ? [
                'id' => $booking->service->id,
                'name' => $booking->service->name,
                'price' => (float) $booking->service->price,
            ] : null,
            'customer' => $booking->user ? [
                'id' => $booking->user->id,
                'name' => $booking->user->name,
                'phone' => $booking->user->phone,
                // بسته‌ی 5ه: «بدقول: N بار نیامد در M روز» یا null
                'no_show_label' => $noShowLabels !== null
                    ? ($noShowLabels[$booking->user_id] ?? null)
                    : app(\App\Services\Booking\NoShowRecord::class)->label($booking->user),
            ] : null,
            // بسته‌ی 5ه: false = بدون پیش‌پرداخت (کل مبلغ در سالن)
            'prepayment_required' => (bool) $booking->prepayment_required,
            'prepayment_amount' => (float) $booking->prepayment_amount,
            'discount_amount' => (float) $booking->discount_amount,
            'remaining_amount' => $booking->remaining_amount,
            // آنچه متخصص در سالن می‌گیرد: نوبت پرداخت‌نشده = کل قیمت منهای تخفیف (۲۰۲۶-۱۰-۲۲)
            'due_at_salon' => $booking->due_at_salon,
            'source' => $booking->source,
            'notes' => $booking->notes,
            'cancellation_reason' => $booking->cancellation_reason,
            'cancelled_by' => $booking->cancelled_by,
            // جلسه‌ی دوره‌ی درمانی (بسته‌ی ب) — null برای نوبت عادی
            'course' => CoursePresenter::bookingCourse($booking),
            // بسته‌ی ۵د (قاعده‌ی بسته‌ی ۳ وب): «تأیید» فقط برای منتظر تأییدِ آینده، «انجام شد» فقط وقتی ساعت نوبت رسیده —
            // اپ‌های قدیمی هم دیگر «انجام شد» را روی نوبت روزهای آینده نشان نمی‌دهند
            'actions' => [
                'confirm' => $booking->status === 'pending' && $time !== null && $time->gt(now()),
                'cancel' => ! in_array($booking->status, ['cancelled', 'completed', 'no_show'], true),
                'complete' => $booking->status === 'confirmed' && $time !== null && $time->lte(now()),
                'no_show' => $booking->canBeMarkedNoShow(),
            ],
        ];
    }

    /** «امروز»، «فردا جمعه ۶ آذر» یا «یکشنبه ۱۵ آذر» */
    public static function dayLabel(\DateTimeInterface $time): string
    {
        $day = \Illuminate\Support\Carbon::instance($time);

        return match (true) {
            $day->isToday() => 'امروز',
            $day->isTomorrow() => 'فردا '.\App\Support\Fa::date($day),
            default => \App\Support\Fa::date($day),
        };
    }

    private static function primaryKind(Booking $booking): string
    {
        return match (true) {
            $booking->status === 'pending' && $booking->booking_time->gt(now()) => 'confirm',
            $booking->status === 'confirmed' && $booking->booking_time->lte(now()) => 'complete',
            default => 'details',
        };
    }
}
