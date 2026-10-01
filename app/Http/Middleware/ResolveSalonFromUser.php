<?php

namespace App\Http\Middleware;

use App\Support\CurrentSalon;
use App\Support\SalonOfNotifiable;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * routeهای API کاربر (auth:sanctum، بیرون از /s/{salon_slug}) سالن جاری نداشتند و کوئری‌هایشان بدون فیلتر سالن اجرا
 * می‌شد؛ در حالت سخت‌گیر BelongsToSalon (۲۰۲۶-۱۰-۰۱) خطا می‌دادند. سالن از خود کاربر: مشتری salon_id دارد، کادر از
 * سالنی که در آن عضو است (SalonOfNotifiable). کاربر بدون سالن → بدون سالن (کوئری سالن‌دار خطا می‌دهد، نه نشت).
 */
class ResolveSalonFromUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $current = app(CurrentSalon::class);
        $user = $request->user();

        if ($user && $current->id() === null) {
            $salonId = $user->salon_id ?? SalonOfNotifiable::resolve($user);
            $salon = $salonId ? \App\Models\Salon::find($salonId) : null;
            if ($salon) {
                $current->set($salon);
            }
        }

        return $next($request);
    }
}
