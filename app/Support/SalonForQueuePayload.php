<?php

namespace App\Support;

use App\Models\Salon;
use Illuminate\Support\Facades\Queue;

/**
 * سالنِ زمان dispatch همراه payload هر job صف می‌رود و worker پیش از unserialize همان را ست می‌کند (حالت سخت‌گیر
 * BelongsToSalon، ۲۰۲۶-۱۰-۰۱). لازم است چون SerializesModels رابطه‌های بارشده‌ی مدل‌ها (مثلاً booking->service) را هنگام
 * بازسازی دوباره با scope سالن می‌خواند — پیش از آنکه pipe باس (SetSalonForQueuedJob) فرصت اجرا داشته باشد.
 * job بدون سالن زمان dispatch (cron، allSalons) سالن را از خودش می‌گیرد (salonId() / SetSalonForQueuedJob).
 */
class SalonForQueuePayload
{
    private static bool $setByUs = false;

    public static function register(): void
    {
        Queue::createPayloadUsing(fn () => ['salon_id' => app(CurrentSalon::class)->id()]);
    }

    public function processing(object $event): void
    {
        $current = app(CurrentSalon::class);
        $salonId = $event->job->payload()['salon_id'] ?? null;

        self::$setByUs = false;
        if ($salonId && $current->id() === null && ! $current->allowsAllSalons() && ($salon = Salon::find($salonId))) {
            $current->set($salon);
            self::$setByUs = true;
        }
    }

    public function finished(object $event): void
    {
        if (self::$setByUs) {
            app(CurrentSalon::class)->clear();
            self::$setByUs = false;
        }
    }
}
