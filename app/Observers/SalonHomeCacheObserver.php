<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * بسته‌ی ۷ (آزمون سالن تازه روی production، ۲۰۲۶-۱۱-۲۶): صفحه‌ی خانه‌ی سالن خدمات، کارشناسان، آمار و نظرها را ۳۰ دقیقه کش می‌کند
 * (User\HomeController). سالن تازه بعد از افزودن کارشناس تا نیم ساعت «کارشناس» نداشت. هر تغییر در همین داده‌ها کش همان سالن را پاک می‌کند.
 */
class SalonHomeCacheObserver
{
    public const KEYS = ['home_services', 'home_specialists', 'home_default_honest', 'home_reviews'];

    public function saved(Model $model): void
    {
        $this->forget($model);
    }

    public function deleted(Model $model): void
    {
        $this->forget($model);
    }

    public static function forgetSalon(?int $salonId): void
    {
        if (! $salonId) {
            return;
        }
        foreach (self::KEYS as $key) {
            Cache::forget("{$key}:{$salonId}");
        }
    }

    private function forget(Model $model): void
    {
        $salonId = $model->getAttribute('salon_id');
        if (! $salonId && $model->getAttribute('specialist_id')) {
            $salonId = \App\Models\Specialist::withoutGlobalScopes()->whereKey($model->getAttribute('specialist_id'))->value('salon_id');
        }

        self::forgetSalon($salonId ? (int) $salonId : null);
    }
}
