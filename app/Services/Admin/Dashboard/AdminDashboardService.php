<?php

namespace App\Services\Admin\Dashboard;

use App\Models\WalletSetting;
use App\Repositories\Contracts\BeautyServiceRepositoryInterface;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\SpecialistRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminDashboardService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly RoleRepositoryInterface $roleRepository,
        private readonly BookingRepositoryInterface $bookingRepository,
        private readonly SpecialistRepositoryInterface $specialistRepository,
        private readonly BeautyServiceRepositoryInterface $beautyServiceRepository,
    ) {}

    /**
     * Full dashboard home page data (controller dashboard() method).
     *
     * Note: popularServices/topSpecialists come from the enriched methods (trend/performance_score)
     * and their range is "last 30 days", not all-time like before — as per the project's decision to
     * actually use this data instead of keeping it as dead code.
     */
    public function getOverviewData(): array
    {
        // ⭐ فاز ۲ SaaS، محور «۲» (تصمیم تأییدشده ۲۰۲۶-۰۹-۱۹): قبل از این فیکس، این متد بدون هیچ
        // چک permission‌ای totalRevenue/weeklyRevenue رو محاسبه و به داشبورد اصلی (اولین صفحه‌ای
        // که هر ادمین از جمله «منشی» می‌بینه) پاس می‌داد — یعنی محدودیت مالی manage-wallet که
        // routes/web.php روی wallet/billing/reports اعمال می‌کنه، برای همین دو رقم روی خودِ
        // داشبورد اصلاً وجود نداشت. حالا این دو مقدار فقط برای کاربری که manage-wallet داره
        // محاسبه می‌شن (bypass همیشگی is_admin طبق User::hasPermission() اینجا هم برقراره)؛
        // dashboard.blade.php هم با @permission('manage-wallet') کارت درآمد و نمودار رو مخفی
        // می‌کنه — این دو لایه با هم کار می‌کنن، نه جایگزین هم.
        $canViewFinancials = auth()->user()?->hasPermission('manage-wallet') ?? false;

        [$commissionRate, $commissionFactor] = $this->getCommissionRateAndFactor();

        $todayBookingsCount = $this->bookingRepository->query()->whereDate('booking_time', today())->count();
        $totalRevenue = null;
        if ($canViewFinancials) {
            $rawRevenue = $this->bookingRepository->query()->where('payment_status', 'paid')->sum('prepayment_amount');
            $totalRevenue = (int) ($rawRevenue * $commissionFactor);
        }
        $usersCount = $this->userRepository->count();
        $specialistsCount = $this->specialistRepository->count();
        $rolesCount = $this->roleRepository->count();

        $roles = $this->roleRepository->getTopByUserCount(4);

        $popularServices = $this->getPopularServicesWithTrend()
            ->take(4)
            ->map(fn (array $item) => (object) $item);

        $topSpecialists = $this->getActiveSpecialistsWithPerformance()
            ->take(3)
            ->map(fn (array $item) => (object) $item);

        $recentBookings = $this->bookingRepository->query()
            ->with(['user', 'service'])
            ->latest()
            ->take(4)
            ->get();

        $weeklyRevenue = collect();
        if ($canViewFinancials) {
            $weeklyRevenue = $this->bookingRepository->query()
                ->where('payment_status', 'paid')
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
        }

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
            'totalBookings' => $this->bookingRepository->count(),
            'todayBookings' => $this->bookingRepository->query()->whereDate('created_at', today())->count(),
            'totalServices' => $this->beautyServiceRepository->count(),
            'totalSpecialists' => $this->specialistRepository->count(),
            'totalUsers' => $this->userRepository->count(),
            'totalRevenue' => $this->bookingRepository->query()->where('payment_status', 'paid')->sum('prepayment_amount'),
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

        $currentMonthServices = $this->beautyServiceRepository->query()
            ->withCount(['bookings' => function ($query) use ($lastMonth) {
                $query->where('created_at', '>=', $lastMonth);
            }])
            ->withSum(['bookings' => function ($query) use ($lastMonth) {
                $query->where('created_at', '>=', $lastMonth);
            }], 'prepayment_amount');

        $previousMonthServices = $this->beautyServiceRepository->query()
            ->withCount(['bookings' => function ($query) use ($previousMonth, $lastMonth) {
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

        return $this->specialistRepository->query()
            ->withCount(['bookings' => function ($query) use ($lastMonth) {
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
        $commissionRate = WalletSetting::get()->admin_commission_percentage ?? 10;

        return [$commissionRate, $commissionRate / 100];
    }
}
