<?php

namespace App\Services\Admin\Dashboard;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Role;
use App\Models\Specialist;
use App\Models\User;
use App\Models\WalletSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminDashboardService
{
    /**
     * Full dashboard home page data (controller dashboard() method).
     *
     * Note: popularServices/topSpecialists come from the enriched methods (trend/performance_score)
     * and their range is "last 30 days", not all-time like before — as per the project's decision to
     * actually use this data instead of keeping it as dead code.
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

        $popularServices = $this->getPopularServicesWithTrend()
            ->take(4)
            ->map(fn (array $item) => (object) $item);

        $topSpecialists = $this->getActiveSpecialistsWithPerformance()
            ->take(3)
            ->map(fn (array $item) => (object) $item);

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
     * Raw overall statistics (getData() method of the Analytics controller).
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
     * Popular services with percentage change from the previous month (last 30 days vs. previous 30 days).
     * Consumer: dashboard() (Popular Services card) and getPopularServices() (Analytics API).
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
     * Active professionals with completion rate, score, and performance score — all on the same "last 30 days" time frame
     * (Fixing the bug of the previous version of Blade that sets bookings_count as "today" but completion as "all-time"
     * counted and had an additional N+1 query).
     * Consumer: dashboard() (specialists table) and getActiveSpecialists() (analytics API).
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

    private function getCommissionRateAndFactor(): array
    {
        $commissionRate = WalletSetting::first()->admin_commission_percentage ?? 10;

        return [$commissionRate, $commissionRate / 100];
    }
}
