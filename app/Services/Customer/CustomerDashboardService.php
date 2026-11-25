<?php

namespace App\Services\Customer;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\User;
use App\Services\Api\CustomerPresenter;
use App\Support\CurrentSalon;
use App\Support\Features\Feature;
use App\Support\SalonMapLocation;
use Illuminate\Support\Carbon;

/**
 * داشبورد مشتری (بسته‌ی ۴ ظاهری، ۲۰۲۶-۱۱-۲۵؛ پیش‌نمایش CuAfterDash/CuAfterMobile): کارت «نوبت امروز» یا «نوبت بعدی» با
 * مسیریابی/تقویم/تغییر زمان، کیف پول، باشگاه (فقط با امکان سالن)، «نوبت‌های بعدی» در یک فهرست، «نظرتان چه بود؟» برای آخرین
 * نوبت انجام‌شده‌ی بی‌نظر، و «دوباره رزرو کنید» (آخرین خدمت + یک پیشنهاد؛ تصمیم «الف»).
 */
class CustomerDashboardService
{
    public function overview(User $user, ?Carbon $now = null): array
    {
        $now ??= Carbon::now();
        $salon = app(CurrentSalon::class)->get();

        $open = Booking::query()->where('user_id', $user->id)
            ->whereIn('status', CustomerBookingListFilter::OPEN_STATUSES)
            ->where('booking_time', '>=', $now->copy()->subHours(6))
            ->with(['service', 'specialist'])
            ->orderBy('booking_time')
            ->limit(12)
            ->get()
            ->filter(fn (Booking $b) => $b->booking_time->copy()->addMinutes((int) ($b->service?->duration ?: 30))->gt($now))
            ->values();

        $next = $open->first();
        $lastDone = Booking::query()->where('user_id', $user->id)->where('status', 'completed')
            ->with(['service', 'specialist'])->orderByDesc('booking_time')->first();
        $toRate = $lastDone && $lastDone->rating === null ? $lastDone : null;

        $loyaltyOn = $salon?->hasFeature(Feature::Loyalty) ?? false;

        return [
            'todayPersian' => \App\Support\Fa::date($now),
            'todayCount' => $open->filter(fn ($b) => $b->booking_time->isSameDay($now))->count(),
            'nextBooking' => $next,
            'nextIsToday' => $next?->booking_time->isSameDay($now) ?? false,
            'canChange' => $next ? $next->booking_time->gt($now->copy()->addHours(CustomerPresenter::CANCEL_DEADLINE_HOURS)) : false,
            'changeHours' => CustomerPresenter::CANCEL_DEADLINE_HOURS,
            'mapUrl' => $salon ? SalonMapLocation::directionsUrl($salon) : null,
            'salon' => $salon,
            'walletBalance' => (float) ($user->wallet?->balance ?? 0),
            'loyaltyOn' => $loyaltyOn,
            'loyaltyPoints' => $loyaltyOn ? (int) app(\App\Repositories\Contracts\LoyaltyPointRepositoryInterface::class)->sumForUser($user->id) : 0,
            'upcoming' => $open->slice(1)->take(4)->values(),
            'toRate' => $toRate,
            'rebook' => $lastDone,
            'suggestion' => $this->suggestion($user, $lastDone?->service_id),
        ];
    }

    /**
     * پیشنهاد دوم «دوباره رزرو کنید» (تصمیم «الف»): پرطرفدارترین خدمت سالن که مشتری هنوز نگرفته، اول از دسته‌هایی که قبلاً گرفته.
     */
    public function suggestion(User $user, ?int $exceptServiceId = null): ?BeautyService
    {
        $taken = Booking::query()->where('user_id', $user->id)->pluck('service_id')->filter()->unique();
        $categories = BeautyService::query()->whereIn('id', $taken)->pluck('category_id')->filter()->unique();

        $base = fn () => BeautyService::query()
            ->whereNotIn('id', $taken->push($exceptServiceId)->filter()->all())
            ->withCount('bookings')
            ->orderByDesc('bookings_count')
            ->orderBy('id');

        return ($categories->isNotEmpty() ? $base()->whereIn('category_id', $categories)->first() : null)
            ?? $base()->first();
    }
}
