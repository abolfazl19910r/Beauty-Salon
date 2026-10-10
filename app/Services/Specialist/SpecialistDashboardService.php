<?php

namespace App\Services\Specialist;

use App\Models\Specialist;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Traits\HasJalaliDates;
use Carbon\Carbon;

class SpecialistDashboardService
{
    use HasJalaliDates;

    /**
     * ⭐ «برنامه‌ی امروز» و «۷ روز آینده» (تصمیم ۲۰۲۶-۱۰-۱۰، بسته‌ی ۲ اپلیکیشن — همین قاعده برای API همکار):
     * همه‌ی نوبت‌های فعال، چه پرداخت‌شده چه نه. قبلاً فقط payment_status=paid می‌آمد: نوبت دستی مدیر که پولش حضوری
     * گرفته می‌شود (unpaid) پنهان بود و نوبت لغوشده‌ی پرداخت‌شده دیده می‌شد. لغوشده و pending_payment (مشتری هنوز
     * در درگاه؛ اگر نپردازد خودکار لغو می‌شود) نمایش داده نمی‌شوند. درآمدها همچنان فقط پرداخت‌شده‌ی لغونشده‌اند.
     */
    public const AGENDA_STATUSES = ['pending', 'confirmed', 'completed'];

    public function __construct(private readonly BookingRepositoryInterface $bookingRepository) {}

    public function getDashboardData(Specialist $specialist): array
    {
        return [
            'todaySchedule' => $this->getTodaySchedule($specialist),
            'todayPersian' => $this->toJalali(Carbon::now(), 'l، j F Y'),
            'todayBookingsCount' => $this->getTodayBookingsCount($specialist),
            'todayRevenue' => $this->getTodayRevenue($specialist),
            'monthBookingsCount' => $this->getMonthBookingsCount($specialist),
            'monthRevenue' => $this->getMonthRevenue($specialist),
            'averageRating' => $this->getAverageRating($specialist),
            'upcomingBookings' => $this->getUpcomingBookings($specialist),
            'recentReviews' => $this->getRecentReviews($specialist),
            'weeklyRevenue' => $this->getWeeklyRevenue($specialist),
            'allBookingsCount' => $this->countByStatus($specialist, null),
            'confirmedBookingsCount' => $this->countByStatus($specialist, 'confirmed'),
            'pendingBookingsCount' => $this->countByStatus($specialist, 'pending'),
            'completedBookingsCount' => $this->countByStatus($specialist, 'completed'),
        ];
    }

    public function getTodaySchedule(Specialist $specialist)
    {
        return $this->bookingRepository->query()->where('specialist_id', $specialist->id)
            ->whereDate('booking_time', Carbon::today())
            ->whereIn('status', self::AGENDA_STATUSES)
            ->with(['service', 'user'])
            ->orderBy('booking_time', 'asc')
            ->get();
    }

    private function getTodayBookingsCount(Specialist $specialist): int
    {
        return $this->bookingRepository->query()->where('specialist_id', $specialist->id)
            ->whereDate('booking_time', Carbon::today())
            ->whereIn('status', self::AGENDA_STATUSES)
            ->count();
    }

    public function getTodayRevenue(Specialist $specialist): float
    {
        return $this->bookingRepository->query()->where('specialist_id', $specialist->id)
            ->whereDate('booking_time', Carbon::today())
            ->where('payment_status', 'paid')
            ->where('status', '!=', 'cancelled')
            ->sum('prepayment_amount');
    }

    private function getMonthBookingsCount(Specialist $specialist): int
    {
        return $this->bookingRepository->query()->where('specialist_id', $specialist->id)
            ->whereMonth('booking_time', Carbon::now()->month)
            ->whereYear('booking_time', Carbon::now()->year)
            ->where('payment_status', 'paid')
            ->count();
    }

    private function getMonthRevenue(Specialist $specialist): float
    {
        return $this->bookingRepository->query()->where('specialist_id', $specialist->id)
            ->whereMonth('booking_time', Carbon::now()->month)
            ->whereYear('booking_time', Carbon::now()->year)
            ->where('payment_status', 'paid')
            ->where('status', '!=', 'cancelled')
            ->sum('prepayment_amount');
    }

    private function getAverageRating(Specialist $specialist): float
    {
        return $this->bookingRepository->query()->where('specialist_id', $specialist->id)
            ->whereNotNull('rating')
            ->avg('rating') ?: 0;
    }

    private function getUpcomingBookings(Specialist $specialist)
    {
        $bookings = $this->bookingRepository->query()->where('specialist_id', $specialist->id)
            ->where('booking_time', '>', Carbon::now())
            ->where('booking_time', '<=', Carbon::now()->addDays(7))
            ->whereIn('status', self::AGENDA_STATUSES)
            ->with(['service', 'user'])
            ->orderBy('booking_time', 'asc')
            ->get();

        return $bookings->each(function ($booking) {
            $booking->booking_date_persian = $this->toJalali($booking->booking_time);
            $booking->status_fa = match ($booking->status) {
                'pending' => 'در انتظار تایید',
                'confirmed' => 'تایید شده',
                'completed' => 'انجام شده',
                'cancelled' => 'لغو شده',
                default => 'نامشخص',
            };
        });
    }

    private function getRecentReviews(Specialist $specialist)
    {
        return $this->bookingRepository->query()->where('specialist_id', $specialist->id)
            ->whereNotNull('review')
            ->with('user')
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();
    }

    private function getWeeklyRevenue(Specialist $specialist): array
    {
        $weeklyRevenue = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $revenue = $this->bookingRepository->query()->where('specialist_id', $specialist->id)
                ->whereDate('booking_time', $date)
                ->where('payment_status', 'paid')
                ->where('status', '!=', 'cancelled')
                ->sum('prepayment_amount');

            $weeklyRevenue[] = [
                'date' => $this->toJalali($date, 'm/d'),
                'total' => $revenue,
            ];
        }

        return $weeklyRevenue;
    }

    private function countByStatus(Specialist $specialist, ?string $status): int
    {
        $query = $this->bookingRepository->query()->where('specialist_id', $specialist->id)
            ->where('payment_status', 'paid');

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->count();
    }
}
