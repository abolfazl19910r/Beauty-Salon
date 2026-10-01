<?php

namespace App\Support;

use App\Models\Salon;

/**
 * اعلانی که بدون سالن جاری فرستاده می‌شود (حالت سخت‌گیر BelongsToSalon، ۲۰۲۶-۱۰-۰۱): پیش از هر کانال، سالنِ رکوردی
 * که اعلان درباره‌ی آن است (SalonOfQueuedJob) یا سالنِ گیرنده (SalonOfNotifiable) ست و بعد از ارسال برداشته می‌شود.
 * اگر سالنی ست است یا داخل allSalons() هستیم، دست نمی‌زند.
 */
class SalonForNotification
{
    /** @var list<bool> برای هر ارسال باز: آیا ما سالن را ست کردیم */
    private static array $stack = [];

    public function sending(object $event): void
    {
        $current = app(CurrentSalon::class);

        if ($current->id() !== null || $current->allowsAllSalons()) {
            self::$stack[] = false;

            return;
        }

        $salonId = SalonOfQueuedJob::resolve($event->notification) ?? SalonOfNotifiable::resolve($event->notifiable);
        $salon = $salonId ? Salon::find($salonId) : null;

        if ($salon) {
            $current->set($salon);
        }
        self::$stack[] = (bool) $salon;
    }

    public function finished(object $event): void
    {
        if (array_pop(self::$stack)) {
            app(CurrentSalon::class)->clear();
        }
    }
}
