<?php

namespace App\Services\Specialist;

use App\Models\Booking;
use App\Models\Specialist;
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;

class SpecialistDashboardService
{
    public function getDashboardData(Specialist $specialist): array
    {
        return [
            'todaySchedule'          => $this->getTodaySchedule($specialist),
            'todayPersian'           => Jalalian::fromCarbon(Carbon::now())->format('l، j F Y'),
            'todayBookingsCount'     => $this->getTodayBookingsCount($specialist),
            'todayRevenue'           => $this->getTodayRevenue($specialist),
            'monthBookingsCount'     => $this->getMonthBookingsCount($specialist),
            'monthRevenue'           => $this->getMonthRevenue($specialist),
            'averageRating'          => $this->getAverageRating($specialist),
            'upcomingBookings'       => $this->getUpcomingBookings($specialist),
            'recentReviews'          => $this->getRecentReviews($specialist),
            'weeklyRevenue'          => $this->getWeeklyRevenue($specialist),
            'allBookingsCount'       => $this->countByStatus($specialist, null),
            'confirmedBookingsCount' => $this->countByStatus($specialist, 'confirmed'),
            'pendingBookingsCount'   => $this->countByStatus($specialist, 'pending'),
            'completedBookingsCount' => $this->countByStatus($specialist, 'completed'),
        ];
    }

    private function getTodaySchedule(Specialist $specialist)
    {
        return Booking::where('specialist_id', $specialist->id)
            ->whereDate('booking_time', Carbon::today())
            ->where('payment_status', 'paid')
            ->with(['service', 'user'])
            ->orderBy('booking_time', 'asc')
            ->get();
    }

    private function getTodayBookingsCount(Specialist $specialist): int
    {
        return Booking::where('specialist_id', $specialist->id)
            ->whereDate('booking_time', Carbon::today())
            ->where('payment_status', 'paid')
            ->count();
    }

    private function getTodayRevenue(Specialist $specialist): float
    {
        return Booking::where('specialist_id', $specialist->id)
            ->whereDate('booking_time', Carbon::today())
            ->where('payment_status', 'paid')
            ->where('status', '!=', 'cancelled')
            ->sum('prepayment_amount');
    }

    private function getMonthBookingsCount(Specialist $specialist): int
    {
        return Booking::where('specialist_id', $specialist->id)
            ->whereMonth('booking_time', Carbon::now()->month)
            ->whereYear('booking_time', Carbon::now()->year)
            ->where('payment_status', 'paid')
            ->count();
    }

    private function getMonthRevenue(Specialist $specialist): float
    {
        return Booking::where('specialist_id', $specialist->id)
            ->whereMonth('booking_time', Carbon::now()->month)
            ->whereYear('booking_time', Carbon::now()->year)
            ->where('payment_status', 'paid')
            ->where('status', '!=', 'cancelled')
            ->sum('prepayment_amount');
    }

    private function getAverageRating(Specialist $specialist): float
    {
        return Booking::where('specialist_id', $specialist->id)
            ->whereNotNull('rating')
            ->avg('rating') ?: 0;
    }

    private function getUpcomingBookings(Specialist $specialist)
    {
        $bookings = Booking::where('specialist_id', $specialist->id)
            ->where('booking_time', '>', Carbon::now())
            ->where('booking_time', '<=', Carbon::now()->addDays(7))
            ->where('payment_status', 'paid')
            ->with(['service', 'user'])
            ->orderBy('booking_time', 'asc')
            ->get();

        return $bookings->each(function ($booking) {
            $booking->booking_date_persian = Jalalian::fromCarbon($booking->booking_time)->format('Y/m/d');
            $booking->status_fa = match ($booking->status) {
                'pending'   => 'در انتظار تایید',
                'confirmed' => 'تایید شده',
                'completed' => 'انجام شده',
                'cancelled' => 'لغو شده',
                default     => 'نامشخص',
            };
        });
    }

    private function getRecentReviews(Specialist $specialist)
    {
        return Booking::where('specialist_id', $specialist->id)
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
            $revenue = Booking::where('specialist_id', $specialist->id)
                ->whereDate('booking_time', $date)
                ->where('payment_status', 'paid')
                ->where('status', '!=', 'cancelled')
                ->sum('prepayment_amount');

            $weeklyRevenue[] = [
                'date'  => Jalalian::fromCarbon($date)->format('m/d'),
                'total' => $revenue,
            ];
        }

        return $weeklyRevenue;
    }

    private function countByStatus(Specialist $specialist, ?string $status): int
    {
        $query = Booking::where('specialist_id', $specialist->id)
            ->where('payment_status', 'paid');

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->count();
    }
}
