<?php

namespace App\Support;

use App\Models\Specialist;

/**
 * نمای عمومی یک متخصص برای مشتری/بازدیدکننده (JSON وب و API اپ «ماهرو»، ۲۰۲۶-۱۰-۱۱).
 *
 * قبلاً JSON صفحه‌های عمومی متخصص‌ها (حتی بدون ورود) خود مدل را برمی‌گرداند: شماره، ایمیل، user_id و درصد
 * کمیسیون متخصص هم می‌رفت. فقط چیزی که روی صفحه‌ی عمومی دیده می‌شود.
 */
final class PublicSpecialist
{
    public static function present(Specialist $specialist): array
    {
        $avg = $specialist->rating_avg ?? $specialist->bookings_avg_rating ?? $specialist->reviews_avg_rating ?? null;

        return array_filter([
            'id' => $specialist->id,
            'name' => $specialist->name,
            'photo_url' => $specialist->photoUrl(),
            'rating_avg' => $avg !== null ? round((float) $avg, 1) : null,
            'rating_count' => isset($specialist->rating_count) ? (int) $specialist->rating_count : ($specialist->reviews_count ?? null),
            'completed_bookings' => isset($specialist->completed_bookings) ? (int) $specialist->completed_bookings : null,
            'services' => $specialist->relationLoaded('services')
                ? $specialist->services->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values()->all()
                : null,
        ], fn ($v) => $v !== null);
    }
}
