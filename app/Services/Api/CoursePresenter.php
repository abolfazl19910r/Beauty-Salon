<?php

namespace App\Services\Api;

use App\Models\Booking;
use App\Models\CoursePackage;
use App\Models\CourseSessionNote;
use App\Models\CourseSessionPhoto;
use App\Models\CustomerCourse;
use App\Models\CustomerCoursePayment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Morilog\Jalali\Jalalian;

/**
 * شکل دوره‌ی درمانی در API هر دو اپ (بسته‌ی ب). مبلغ‌ها تومان؛ زمان‌ها ISO 8601 و شمسی.
 * آدرس عکس‌ها مسیرهای API با همان توکن Bearer است (دیسک خصوصی؛ هرگز لینک عمومی).
 */
class CoursePresenter
{
    public static function package(CoursePackage $package): array
    {
        return [
            'id' => $package->id,
            'name' => $package->name,
            'description' => $package->description,
            'sessions_count' => (int) $package->sessions_count,
            'interval_days' => (int) $package->interval_days,
            'validity_days' => (int) $package->validity_days,
            'price' => (float) $package->price,
            'list_price' => $package->listPrice(),
            'saving' => (float) $package->savingAmount(),
            'min_prepayment' => (float) $package->minPrepaymentFor((float) $package->price),
            'service' => $package->service ? ['id' => $package->service->id, 'name' => $package->service->name, 'duration_minutes' => (int) $package->service->duration] : null,
            // بسته‌ی چندخدمتی (ادامه ۷): همه‌ی خدمت‌های هر جلسه و مدت جلسه
            'services' => $package->sessionServices()->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values(),
            'session_minutes' => $package->sessionDuration(),
            'specialist' => $package->specialist ? ['id' => $package->specialist->id, 'name' => $package->specialist->name] : null,
        ];
    }

    public static function course(CustomerCourse $course, ?int $reserved = null): array
    {
        $reserved ??= $course->reservedCount();
        $blocker = $course->bookingBlocker($reserved);

        return [
            'id' => $course->id,
            'name' => $course->name,
            'status' => $course->status,
            'status_label' => $course->statusLabel(),
            'sessions_total' => $course->sessions_total,
            'sessions_used' => $course->sessions_used,
            'sessions_burned' => $course->sessions_burned,
            'sessions_reserved' => $reserved,
            'sessions_remaining' => $course->remainingSessions($reserved),
            'paid_sessions' => $course->paidSessions(),
            'price_total' => $course->price_total,
            'amount_paid' => $course->amount_paid,
            'balance_due' => $course->balanceDue(),
            'min_prepayment' => $course->min_prepayment,
            'discount_amount' => $course->discount_amount,
            'service_id' => $course->service_id,
            'services_label' => $course->servicesLabel(),
            'session_minutes' => $course->sessionMinutes(),
            'specialist_id' => $course->specialist_id,
            'purchased_at' => self::time($course->purchased_at),
            'expires_at' => self::time($course->expires_at),
            'expires_at_jalali' => $course->expires_at ? Jalalian::fromCarbon($course->expires_at)->format('Y/m/d') : null,
            'next_due_at' => self::time($course->next_due_at),
            'photo_consent' => $course->hasPhotoConsent(),
            'refunded_amount' => $course->refunded_amount,
            'actions' => [
                'pay' => $course->balanceDue() > 0 && in_array($course->status, ['pending_payment', 'active'], true),
                'book' => $blocker === null,
            ],
            'book_blocker' => $blocker,
        ];
    }

    /**
     * جزئیات «دوره‌ی من»: جلسه‌ها (نوبت‌ها) + یادداشت‌های قابل نمایش برای مشتری + عکس‌ها + پرداخت‌ها.
     *
     * @param  Collection<int, Booking>  $bookings
     */
    public static function detail(CustomerCourse $course, Collection $bookings, array $records, string $photoRoute): array
    {
        return self::course($course) + [
            'sessions' => $bookings->map(fn (Booking $b) => self::session($b, $records, $photoRoute, $course))->values(),
            'payments' => $course->payments()->latest('id')->get()->map(fn (CustomerCoursePayment $p) => [
                'amount' => (float) $p->amount,
                'method' => $p->method,
                'created_at' => self::time($p->created_at),
            ])->values(),
        ];
    }

    public static function session(Booking $booking, array $records, string $photoRoute, CustomerCourse $course): array
    {
        /** @var CourseSessionNote|null $note */
        $note = $records['notes']->get($booking->id);
        $photos = $records['photos']->get($booking->id, collect());

        return [
            'booking_id' => $booking->id,
            'session_number' => $booking->course_session_number,
            'label' => $booking->course_session_number ? $course->sessionLabel((int) $booking->course_session_number) : null,
            'status' => $booking->status,
            'burned' => (bool) $booking->course_session_burned,
            'booking_time' => self::time($booking->booking_time),
            'booking_time_jalali' => $booking->booking_time ? Jalalian::fromCarbon($booking->booking_time)->format('Y/m/d H:i') : null,
            'specialist' => $booking->specialist ? ['id' => $booking->specialist->id, 'name' => $booking->specialist->name] : null,
            'note' => $note?->note,
            'note_visible_to_customer' => $note ? (bool) $note->visible_to_customer : null,
            'photos' => $photos->map(fn (CourseSessionPhoto $p) => [
                'id' => $p->id,
                'kind' => $p->kind,
                'url' => route($photoRoute, ['photoId' => $p->id]),
            ])->values(),
        ];
    }

    /** خلاصه‌ی دوره روی نوبت (اپ مشتری و همکار) — null برای نوبت عادی */
    public static function bookingCourse(Booking $booking): ?array
    {
        if (! $booking->customer_course_id) {
            return null;
        }
        $course = $booking->relationLoaded('customerCourse')
            ? $booking->customerCourse
            : $booking->customerCourse()->withoutGlobalScopes()->first();
        if (! $course) {
            return null;
        }

        return [
            'id' => $course->id,
            'name' => $course->name,
            'session_number' => $booking->course_session_number,
            'sessions_total' => $course->sessions_total,
            'label' => $booking->course_session_number ? $course->sessionLabel((int) $booking->course_session_number) : null,
            'photo_consent' => $course->hasPhotoConsent(),
        ];
    }

    private static function time(mixed $value): ?string
    {
        return $value ? Carbon::parse($value)->toIso8601String() : null;
    }
}
