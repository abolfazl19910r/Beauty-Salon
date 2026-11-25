<?php

namespace App\Services\Specialist;

use App\Models\Booking;
use App\Models\Specialist;
use Illuminate\Database\Eloquent\Builder;

/**
 * نوبت‌های پنل کارشناس (بسته‌ی ۳ ظاهری، ۲۰۲۶-۱۱-۲۵). «منتظر تأیید» = نوبت آینده‌ی pending همین کارشناس؛
 * نشان شمار منو و شمار تب «منتظر تأیید» از همین یک تعریف می‌آیند تا هیچ‌وقت با هم فرق نکنند.
 */
final class SpecialistBookingListFilter
{
    public static function forSpecialist(Specialist $specialist): Builder
    {
        return Booking::query()->where('specialist_id', $specialist->id);
    }

    public static function waiting(Builder $query): Builder
    {
        return $query->where('status', 'pending')->where('booking_time', '>=', now());
    }

    public static function waitingCount(Specialist $specialist): int
    {
        return self::waiting(self::forSpecialist($specialist))->count();
    }
}
