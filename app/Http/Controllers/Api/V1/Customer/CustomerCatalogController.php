<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Exceptions\Api\ApiException;
use App\Http\Responses\ApiResponse;
use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Specialist;
use App\Models\UserNotification;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\HolidayRepositoryInterface;
use App\Repositories\Contracts\LeaveRepositoryInterface;
use App\Repositories\Contracts\SpecialistScheduleRepositoryInterface;
use App\Services\Api\CustomerPresenter;
use App\Services\LoyaltyService;
use App\Support\PublicSpecialist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Morilog\Jalali\Jalalian;

/**
 * سالن، خدمات، متخصص‌ها و زمان‌های آزاد در اپ «ماهرو» (بسته‌ی ۳) — همه بعد از ورود (تصمیم ۲۰۲۶-۱۰-۱۱).
 * همان داده‌ی صفحه‌ی رزرو وب؛ متخصص بدون شماره/ایمیل (PublicSpecialist). تاریخ‌ها میلادی Y-m-d + شمسی برای نمایش.
 */
class CustomerCatalogController extends CustomerController
{
    /** روزهایی که برای رزرو نشان داده می‌شوند (مثل فرم رزرو وب) */
    public const BOOKING_DAYS_AHEAD = 30;

    public function salonInfo(): JsonResponse
    {
        return ApiResponse::success(CustomerPresenter::salonDetail($this->salon()));
    }

    public function home(Request $request, LoyaltyService $loyalty): JsonResponse
    {
        $user = $this->customer($request);

        $upcoming = Booking::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending_payment', 'pending', 'confirmed'])
            ->where('booking_time', '>=', now())
            ->with(['service', 'specialist'])
            ->orderBy('booking_time')
            ->limit(3)
            ->get();

        return ApiResponse::success([
            'salon' => CustomerPresenter::salonCard($this->salon()),
            'features' => app(\App\Services\Plans\FeatureResolver::class)->enabledKeys($this->salon()),
            'upcoming_bookings' => $upcoming->map(fn (Booking $b) => CustomerPresenter::booking($b))->values(),
            'awaiting_payment_count' => $upcoming->filter(fn (Booking $b) => $b->isPayable() && $b->status === 'pending_payment')->count(),
            'loyalty_points' => $loyalty->getCurrentPoints($user->id),
            'wallet_balance' => (float) ($user->wallet?->balance ?? 0),
            'unread_notifications' => UserNotification::limitToCurrentSalon(UserNotification::query()
                ->where('notifiable_type', $user->getMorphClass())->where('notifiable_id', $user->id))
                ->whereNull('read_at')->count(),
        ]);
    }

    public function services(): JsonResponse
    {
        $services = BeautyService::query()->with('category')->orderBy('name')->get();

        return ApiResponse::success([
            'categories' => Category::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values(),
            'services' => $services->map(fn (BeautyService $s) => CustomerPresenter::service($s))->values(),
        ]);
    }

    public function serviceSpecialists(int $serviceId): JsonResponse
    {
        $service = BeautyService::query()->findOrFail($serviceId);

        return ApiResponse::success($service->specialists()->orderBy('name')->get()
            ->map(fn (Specialist $s) => PublicSpecialist::present($s))->values());
    }

    public function specialists(): JsonResponse
    {
        return ApiResponse::success(Specialist::query()->with('services')->orderBy('name')->get()
            ->map(fn (Specialist $s) => PublicSpecialist::present($s))->values());
    }

    public function specialist(int $specialistId, BookingRepositoryInterface $bookings): JsonResponse
    {
        $specialist = Specialist::query()->with('services')->findOrFail($specialistId);
        $stats = $bookings->getRatingStatsForSpecialist($specialist->id);
        $specialist->rating_avg = $stats['avg'];
        $specialist->rating_count = $stats['count'];
        $specialist->completed_bookings = $stats['completed'];

        return ApiResponse::success(PublicSpecialist::present($specialist) + [
            'reviews' => $bookings->getRecentReviewsForSpecialist($specialist->id, 5)->map(fn ($b) => [
                // فقط نام کوچک مشتری دیگر
                'customer' => strtok((string) $b->user?->name, ' ') ?: 'مشتری',
                'rating' => (int) $b->rating,
                'review' => $b->review,
                'date_jalali' => Jalalian::fromCarbon(Carbon::parse($b->created_at))->format('Y/m/d'),
            ])->values(),
        ]);
    }

    /** GET specialists/{id}/dates?service_id — روزهای کاری آینده (بدون مرخصی/تعطیلی). */
    public function dates(
        Request $request,
        int $specialistId,
        SpecialistScheduleRepositoryInterface $schedules,
        LeaveRepositoryInterface $leaves,
        HolidayRepositoryInterface $holidays,
    ): JsonResponse {
        $specialist = $this->bookableSpecialist($request, $specialistId);

        $dates = [];
        for ($i = 0; $i < self::BOOKING_DAYS_AHEAD; $i++) {
            $day = Carbon::today()->addDays($i);
            $date = $day->toDateString();

            if (! $schedules->findActiveForDay($specialist->id, $day->dayOfWeek)
                || $leaves->hasApprovedLeaveOnDate($specialist->id, $date)
                || $holidays->existsOnDate($specialist->id, $date)) {
                continue;
            }

            $dates[] = [
                'date' => $date,
                'date_jalali' => Jalalian::fromCarbon($day)->format('Y/m/d'),
                'label' => Jalalian::fromCarbon($day)->format('l j F'),
            ];
        }

        return ApiResponse::success($dates);
    }

    /** GET specialists/{id}/slots?date=Y-m-d&service_id — ساعت‌های آزاد با مدت همان خدمت. */
    public function slots(Request $request, int $specialistId): JsonResponse
    {
        $input = $request->validate([
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'service_id' => ['required', 'integer'],
        ]);

        $specialist = $this->bookableSpecialist($request, $specialistId);
        $service = BeautyService::query()->findOrFail((int) $input['service_id']);

        $slots = collect($specialist->getAvailableSlots($input['date'], (int) $service->duration))
            ->map(fn (string $time) => [
                'time' => $time,
                'booking_time' => Carbon::parse($input['date'].' '.$time)->toIso8601String(),
            ])->values();

        return ApiResponse::success($slots, [
            'date' => $input['date'],
            'date_jalali' => Jalalian::fromCarbon(Carbon::parse($input['date']))->format('Y/m/d'),
            'duration_minutes' => (int) $service->duration,
        ]);
    }

    /** متخصص همین سالن که (اگر service_id آمده) این خدمت را انجام می‌دهد؛ وگرنه ۴۰۴/۴۲۲. */
    private function bookableSpecialist(Request $request, int $specialistId): Specialist
    {
        $specialist = Specialist::query()->findOrFail($specialistId);

        $serviceId = $request->input('service_id');
        if ($serviceId !== null && ! $specialist->services()->whereKey((int) $serviceId)->exists()) {
            throw new ApiException('service_not_offered', 'این متخصص این خدمت را انجام نمی‌دهد.', 422);
        }

        return $specialist;
    }
}
