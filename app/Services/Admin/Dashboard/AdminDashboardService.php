<?php

namespace App\Services\Admin\Dashboard;

use App\Models\Booking;
use App\Models\BeautyService;
use App\Models\Specialist;
use App\Models\User;
use App\Models\Role;
use App\Models\WalletSetting;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AdminDashboardService
{
    /**
     * Complete dashboard home page data (controller dashboard() method).
     */
    public function getOverviewData(): array
    {
        [$commissionRate, $commissionFactor] = $this->getCommissionRateAndFactor();

        $todayBookingsCount = Booking::whereDate('booking_time', today())->count();
        $rawRevenue = Booking::where('payment_status', 'paid')->sum('prepayment_amount');
        $totalRevenue = (int) ($rawRevenue * $commissionFactor);
        $usersCount = User::count();
        $specialistsCount = Specialist::count();
        $rolesCount = Role::count();

        $roles = Role::withCount('users')->take(4)->get();

        $popularServices = BeautyService::withCount('bookings')
            ->orderBy('bookings_count', 'desc')
            ->take(4)
            ->get();

        $topSpecialists = Specialist::withCount(['bookings' => function ($query) {
            $query->whereDate('booking_time', today());
        }])
            ->withSum(['bookings' => function ($query) {
                $query->where('payment_status', 'paid');
            }], 'prepayment_amount')
            ->orderBy('bookings_count', 'desc')
            ->take(3)
            ->get();

        $recentBookings = Booking::with(['user', 'service'])
            ->latest()
            ->take(4)
            ->get();

        $weeklyRevenue = Booking::where('payment_status', 'paid')
            ->whereBetween('created_at', [now()->subDays(6)->startOfDay(), now()])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(prepayment_amount) as total'),
                DB::raw('COUNT(*) as bookings_count')
            )
            ->orderBy('date')
            ->get()
            ->map(function ($item) use ($commissionFactor) {
                $item->date = verta($item->date)->format('Y/m/d');
                $item->total = (int) ($item->total * $commissionFactor);
                return $item;
            });

        return compact(
            'commissionRate',
            'todayBookingsCount',
            'totalRevenue',
            'usersCount',
            'specialistsCount',
            'rolesCount',
            'roles',
            'popularServices',
            'topSpecialists',
            'recentBookings',
            'weeklyRevenue'
        );
    }

    /**
     * Raw aggregate statistics (Analytics controller getData() method).
     */
    public function getSummaryStats(): array
    {
        return [
            'totalBookings' => Booking::count(),
            'todayBookings' => Booking::whereDate('created_at', today())->count(),
            'totalServices' => BeautyService::count(),
            'totalSpecialists' => Specialist::count(),
            'totalUsers' => User::count(),
            'totalRevenue' => Booking::where('payment_status', 'paid')->sum('prepayment_amount'),
        ];
    }

    /**
     * Daily revenue in a date range (method getDailyRevenue() of Analytics controller).
     */
    public function getDailyRevenueBetween($startDate, $endDate): Collection
    {
        return Booking::where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(prepayment_amount) as total'),
                DB::raw('COUNT(*) as bookings_count')
            )
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                $item->date = verta($item->date)->format('Y/m/d');
                return $item;
            });
    }

    /**
     * Popular services with percentage change from the previous month (getPopularServices() method of the Analytics controller).
     */
    public function getPopularServicesWithTrend(): Collection
    {
        $lastMonth = now()->subDays(30);
        $previousMonth = now()->subDays(60);

        $currentMonthServices = BeautyService::withCount(['bookings' => function ($query) use ($lastMonth) {
            $query->where('created_at', '>=', $lastMonth);
        }])
            ->withSum(['bookings' => function ($query) use ($lastMonth) {
                $query->where('created_at', '>=', $lastMonth);
            }], 'prepayment_amount');

        $previousMonthServices = BeautyService::withCount(['bookings' => function ($query) use ($previousMonth, $lastMonth) {
            $query->whereBetween('created_at', [$previousMonth, $lastMonth]);
        }])->pluck('bookings_count', 'id');

        return $currentMonthServices
            ->orderByDesc('bookings_count')
            ->take(5)
            ->get(['id', 'name'])
            ->map(function ($service) use ($previousMonthServices) {
                $previousCount = $previousMonthServices[$service->id] ?? 0;
                $currentCount = $service->bookings_count;

                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'bookings_count' => $currentCount,
                    'revenue' => $service->bookings_sum_prepayment_amount,
                    'trend' => $previousCount > 0
                        ? round((($currentCount - $previousCount) / $previousCount) * 100, 1)
                        : 100,
                ];
            });
    }

    /**
     * Active Specialists with Performance Score (getActiveSpecialists() method of the Analytics controller).
     */
    public function getActiveSpecialistsWithPerformance(): Collection
    {
        $lastMonth = now()->subDays(30);

        return Specialist::withCount(['bookings' => function ($query) use ($lastMonth) {
            $query->where('created_at', '>=', $lastMonth);
        }])
            ->withSum(['bookings' => function ($query) use ($lastMonth) {
                $query->where('created_at', '>=', $lastMonth);
            }], 'prepayment_amount')
            ->withAvg(['bookings' => function ($query) use ($lastMonth) {
                $query->where('created_at', '>=', $lastMonth);
            }], 'rating')
            ->withCount(['bookings as successful_bookings' => function ($query) use ($lastMonth) {
                $query->where('created_at', '>=', $lastMonth)
                    ->where('status', 'completed');
            }])
            ->orderByDesc('bookings_count')
            ->take(5)
            ->get(['id', 'name'])
            ->map(function ($specialist) {
                $completion_rate = $specialist->bookings_count > 0
                    ? round(($specialist->successful_bookings / $specialist->bookings_count) * 100, 1)
                    : 0;

                return [
                    'id' => $specialist->id,
                    'name' => $specialist->name,
                    'bookings_count' => $specialist->bookings_count,
                    'revenue' => $specialist->bookings_sum_prepayment_amount,
                    'rating' => round($specialist->bookings_avg_rating, 1),
                    'successful_bookings' => $specialist->successful_bookings,
                    'completion_rate' => $completion_rate,
                    'performance_score' => $this->calculatePerformanceScore($specialist, $completion_rate),
                    'top_performer' => $completion_rate >= 90,
                ];
            });
    }

    /**
     * Dashboard statistics by time period (Analytics controller getDashboardByPeriod() method).
     *
     * @throws InvalidArgumentException When $period is invalid
     */
    public function getStatsByPeriod(string $period): array
    {
        [$commissionRate, $commissionFactor] = $this->getCommissionRateAndFactor();

        switch ($period) {
            case 'today':
                $start = Carbon::today()->startOfDay();
                $end = Carbon::today()->endOfDay();
                $dateColumn = 'booking_time';
                break;
            case 'week':
                $start = Carbon::now()->subDays(6)->startOfDay();
                $end = Carbon::now()->endOfDay();
                $dateColumn = 'created_at';
                break;
            case 'month':
                $start = Carbon::now()->subDays(29)->startOfDay();
                $end = Carbon::now()->endOfDay();
                $dateColumn = 'created_at';
                break;
            default:
                throw new InvalidArgumentException('Invalid period');
        }

        $rawRevenue = Booking::where('payment_status', 'paid')
            ->whereBetween($dateColumn, [$start, $end])
            ->sum('prepayment_amount');

        $stats = [
            'todayBookingsCount' => Booking::whereDate('booking_time', today())->count(),
            'totalRevenue' => (int) ($rawRevenue * $commissionFactor),
            'commissionRate' => $commissionRate,
            'usersCount' => User::count(),
            'specialistsCount' => Specialist::count(),
        ];

        if ($period === 'today') {
            $rows = Booking::where('payment_status', 'paid')
                ->whereBetween('booking_time', [$start, $end])
                ->groupBy(DB::raw('HOUR(booking_time)'))
                ->select(
                    DB::raw('HOUR(booking_time) as hour'),
                    DB::raw('SUM(prepayment_amount) as total'),
                    DB::raw('COUNT(*) as bookings_count')
                )
                ->orderBy('hour')
                ->get();

            $dailyRevenue = $rows->map(function ($item) use ($start, $commissionFactor) {
                $dt = $start->copy()->hour($item->hour);
                return [
                    'date' => verta($dt)->format('H:00'),
                    'total' => (int) ($item->total * $commissionFactor),
                    'bookings_count' => (int) $item->bookings_count,
                ];
            })->values();
        } else {
            $rows = Booking::where('payment_status', 'paid')
                ->whereBetween('created_at', [$start, $end])
                ->groupBy(DB::raw('DATE(created_at)'))
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(prepayment_amount) as total'),
                    DB::raw('COUNT(*) as bookings_count')
                )
                ->orderBy('date')
                ->get();

            $dailyRevenue = $rows->map(function ($item) use ($commissionFactor) {
                return [
                    'date' => verta($item->date)->format('Y/m/d'),
                    'total' => (int) ($item->total * $commissionFactor),
                    'bookings_count' => (int) $item->bookings_count,
                ];
            })->values();
        }

        $popularServices = BeautyService::withCount(['bookings' => function ($q) use ($start, $end, $dateColumn) {
            $q->whereBetween($dateColumn, [$start, $end]);
        }])
            ->withSum(['bookings' => function ($q) use ($start, $end, $dateColumn) {
                $q->whereBetween($dateColumn, [$start, $end]);
            }], 'prepayment_amount')
            ->orderByDesc('bookings_count')
            ->take(5)
            ->get(['id', 'name'])
            ->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'bookings_count' => $s->bookings_count,
                'revenue' => (int) $s->bookings_sum_prepayment_amount,
            ]);

        $recentBookings = Booking::with(['user', 'service'])
            ->whereBetween($dateColumn, [$start, $end])
            ->latest($dateColumn)
            ->take(5)
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'user_name' => optional($b->user)->name ?? '—',
                'service_name' => optional($b->service)->name ?? '—',
                'status' => $b->status,
                'booking_time' => verta($b->booking_time)->format('Y/m/d H:i'),
            ]);

        return [
            'stats' => $stats,
            'dailyRevenue' => $dailyRevenue,
            'popularServices' => $popularServices,
            'recentBookings' => $recentBookings,
        ];
    }

    private function calculatePerformanceScore($specialist, float $completionRate): float
    {
        $scoreFactors = [
            'bookings' => $specialist->bookings_count * 10,
            'completion' => $completionRate,
            'rating' => ($specialist->bookings_avg_rating ?? 0) * 20,
            'revenue' => min(($specialist->bookings_sum_prepayment_amount / 1000000), 100),
        ];

        return round(array_sum($scoreFactors) / 4, 1);
    }

    /**
     * @return array{0: float, 1: float} [Commission rate (percentage), commission factor (0 to 1)]
     */
    private function getCommissionRateAndFactor(): array
    {
        $commissionRate = WalletSetting::first()->admin_commission_percentage ?? 10;

        return [$commissionRate, $commissionRate / 100];
    }
}
