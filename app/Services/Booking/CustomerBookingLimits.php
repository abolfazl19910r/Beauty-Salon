<?php

namespace App\Services\Booking;

use App\Exceptions\BookingLimitException;
use App\Models\Booking;
use App\Models\BookingPolicy;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * سقف نوبت آنلاین هر مشتری (بسته‌ی 5ه، تصمیم‌های ۱۲ تا ۱۵):
 * - روزانه: «روز» = روز خود نوبت به وقت سالن (Asia/Tehran)، نه روز ثبت؛ پیش‌فرض ۱، قابل تنظیم مدیر.
 * - کل نوبت‌های آینده‌ی فعال: پیش‌فرض ۳، قابل تنظیم مدیر.
 * شمرده می‌شوند فقط نوبت‌های فعال (pending_payment، pending، confirmed) همین سالن — جلسه‌ی دوره‌ی درمانی هم. سقف خالی/صفر = بی‌سقف.
 * فقط رزرو آنلاین مشتری (وب و اپ)؛ رزرو دستی کادر معاف است و فقط هشدار می‌گیرد (sameDayCount).
 *
 * هم‌زمانی: داخل تراکنش رزرو اول lockCustomer (ردیف مشتری)، بعد قفل متخصص (BookingService::assertBookingFits)، و فقط بعد از هر دو قفل
 * assertCanBook؛ دو درخواست هم‌زمان همان مشتری (حتی برای دو متخصص) پشت سر هم شمرده می‌شوند. ترتیب قفل همه‌جا: (دوره ←) مشتری ← متخصص.
 * ⚠️ هیچ خواندن عادی پیش از قفل متخصص نباشد: در REPEATABLE READ اولین خواندن عادی snapshot را می‌سازد و سنجش هم‌پوشانی متخصص بعد از
 * انتظار پشت قفل، نوبت تازه‌ی تراکنش دیگر را نمی‌دید (OverlappingBookingConcurrencyTest).
 */
class CustomerBookingLimits
{
    public const ACTIVE_STATUSES = ['pending_payment', 'pending', 'confirmed'];

    /**
     * @param  bool  $countActive  false برای جابه‌جایی (تعداد نوبت‌های فعال عوض نمی‌شود، فقط روزش)
     *
     * @throws BookingLimitException
     */
    public function assertCanBook(int $userId, string $bookingTime, ?int $excludeBookingId = null, bool $countActive = true): void
    {
        $policy = BookingPolicy::current();

        $daily = BookingPolicy::limit($policy->daily_limit);
        if ($daily !== null && $this->sameDayCount($userId, $bookingTime, $excludeBookingId) >= $daily) {
            throw BookingLimitException::daily($daily);
        }

        $active = $countActive ? $this->activeLimitFor($userId, $policy) : null;
        if ($active !== null && $this->activeFutureCount($userId, $excludeBookingId) >= $active) {
            throw BookingLimitException::active($active);
        }
    }

    /** داخل تراکنش، پیش از قفل متخصص و پیش از هر خواندن عادی */
    public function lockCustomer(int $userId): void
    {
        DB::table('users')->where('id', $userId)->lockForUpdate()->first(['id']);
    }

    /** سقف نوبت‌های آینده‌ی فعال این مشتری؛ null = بی‌سقف */
    public function activeLimitFor(int $userId, ?BookingPolicy $policy = null): ?int
    {
        $policy ??= BookingPolicy::current();

        return BookingPolicy::limit($policy->active_limit);
    }

    /** نوبت‌های فعال همین مشتری در روز همین نوبت (برای هشدار رزرو دستی کادر هم) */
    public function sameDayCount(int $userId, string $bookingTime, ?int $excludeBookingId = null): int
    {
        $day = Carbon::parse($bookingTime, config('app.timezone'));

        return Booking::query()
            ->where('user_id', $userId)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->whereBetween('booking_time', [$day->copy()->startOfDay()->format('Y-m-d H:i:s'), $day->copy()->endOfDay()->format('Y-m-d H:i:s')])
            ->when($excludeBookingId, fn ($q) => $q->whereKeyNot($excludeBookingId))
            ->count();
    }

    public function activeFutureCount(int $userId, ?int $excludeBookingId = null): int
    {
        return Booking::query()
            ->where('user_id', $userId)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->where('booking_time', '>=', now()->format('Y-m-d H:i:s'))
            ->when($excludeBookingId, fn ($q) => $q->whereKeyNot($excludeBookingId))
            ->count();
    }
}
