<?php

namespace App\Traits;

use App\Models\Specialist;
use App\Support\CurrentSalon;
use Illuminate\Database\Eloquent\Builder;

/**
 * معادل BelongsToSalon برای جدول‌هایی که salon_id ندارن و از طریق specialist_id به سالن وصلن. وقتی CurrentSalon ست
 * است، فقط ردیف‌های متخصص‌های همون سالن (شامل متخصص حذف‌شده) دیده می‌شن. مثل BelongsToSalon، اتصال مدل در
 * route (SubstituteBindings) قبل از ست‌شدن CurrentSalon انجام می‌شه، پس کنترلرها برای مدل bind‌شده همچنان باید
 * ensureSalonOwnership صدا بزنن.
 */
trait BelongsToSalonThroughSpecialist
{
    protected static function bootBelongsToSalonThroughSpecialist(): void
    {
        static::addGlobalScope('salon', function (Builder $builder) {
            $salonId = app(CurrentSalon::class)->id();

            if ($salonId !== null) {
                $builder->whereIn(
                    $builder->getModel()->getTable().'.specialist_id',
                    Specialist::withoutGlobalScopes()->select('id')->where('salon_id', $salonId)
                );
            }
        });
    }
}
