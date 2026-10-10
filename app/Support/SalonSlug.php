<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * slug یکتا در محدوده‌ی همان سالن (۲۰۲۶-۱۰-۱۱).
 *
 * slugها (دسته‌بندی، خدمت، دسته/پست وبلاگ) قبلاً در کل جدول یکتا بودند؛ سالن دوم با ساختن «رنگ مو»
 * (slug: rng-mo) به ردیف سالن اول برخورد می‌کرد. حالا یکتایی (salon_id, slug) است و اگر دو نام
 * متفاوت یک slug بسازند (مثلاً «رنگ مو» و «رنگ-مو»)، پسوند ‎-2، ‎-3… می‌گیرد.
 *
 * newQueryWithoutScopes هم scope سالن را برمی‌دارد (سالن صریح فیلتر می‌شود) و هم ردیف‌های حذف نرم را
 * می‌بیند — درست، چون قید یکتای دیتابیس آن‌ها را هم می‌شمارد.
 */
class SalonSlug
{
    public static function make(Model $model, string $source): string
    {
        $base = Str::slug($source);
        if ($base === '') {
            $base = Str::lower(Str::random(8));
        }

        $salonId = $model->getAttribute('salon_id') ?? app(CurrentSalon::class)->id();
        $slug = $base;

        for ($i = 2; self::taken($model, $salonId, $slug); $i++) {
            $slug = $base.'-'.$i;
        }

        return $slug;
    }

    private static function taken(Model $model, ?int $salonId, string $slug): bool
    {
        return $model->newQueryWithoutScopes()
            ->where('slug', $slug)
            ->where('salon_id', $salonId)
            ->when($model->exists, fn ($q) => $q->whereKeyNot($model->getKey()))
            ->exists();
    }
}
