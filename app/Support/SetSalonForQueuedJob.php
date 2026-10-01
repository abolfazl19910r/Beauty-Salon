<?php

namespace App\Support;

use App\Models\Salon;
use Closure;

/**
 * Bus pipe (AppServiceProvider): وقتی job یا اعلانی بدون سالن جاری اجرا می‌شود (worker، دستور کنسول، afterResponse)،
 * سالن را از خود job پیدا و فقط برای مدت اجرای همان job ست می‌کند. اگر سالنی ست است یا داخل allSalons() هستیم، دست
 * نمی‌زند. jobی که سالنش پیدا نشود همان‌طور اجرا می‌شود و خودش باید سالن را ست کند یا allSalons() بگوید.
 */
class SetSalonForQueuedJob
{
    public function handle(object $command, Closure $next): mixed
    {
        $current = app(CurrentSalon::class);

        if ($current->id() !== null || $current->allowsAllSalons()) {
            return $next($command);
        }

        $salonId = SalonOfQueuedJob::resolve($command);
        $salon = $salonId ? Salon::find($salonId) : null;

        if (! $salon) {
            return $next($command);
        }

        $current->set($salon);

        try {
            return $next($command);
        } finally {
            $current->clear();
        }
    }
}
