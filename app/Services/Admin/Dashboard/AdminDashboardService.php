<?php

namespace App\Services\Admin\Dashboard;

use App\Repositories\Contracts\BeautyServiceRepositoryInterface;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\SpecialistRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Collection;

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
        // ⭐ فاز ۲ SaaS، محور «۲» (تصمیم ۲۰۲۶-۰۹-۱۹): رقم‌های مالی فقط برای کاربری که manage-wallet دارد محاسبه و نشان داده می‌شوند.
        $canViewFinancials = auth()->user()?->hasPermission('manage-wallet') ?? false;
        $salon = app(\App\Support\CurrentSalon::class)->get();

        // بسته‌ی ۲ ظاهری (۲۰۲۶-۱۱-۲۴، تصمیم‌های «الف»): امروز با جمله‌ی خلاصه، ۴ کارت، نوبت‌های امروز با کار مستقیم،
        // «ماه» = ماه شمسی؛ نمودار، کارت نقش‌ها و «نوبت‌های اخیر» حذف شدند.
        $month = verta();
        $monthStart = \Illuminate\Support\Carbon::instance($month->startMonth()->datetime());

        $todayBookings = $this->bookingRepository->query()
            ->with(['user:id,name,phone', 'service:id,name', 'specialist:id,name'])
            ->whereDate('booking_time', today())
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('booking_time')
            ->get();
        $todayWaiting = $todayBookings->whereIn('status', ['pending', 'pending_payment'])->count();

        // سهم سالن = همان کمیسیونی که هنگام پرداخت در دفتر کیف پول سالن ثبت شد، با نرخ همان کارشناس در همان زمان
        // (قرارداد جدای مدیر با کارشناس)، منهای برگشت‌ها به‌خاطر لغو؛ نه «کل پیش‌پرداخت × نرخ کلی» (آزمون پروداکشن ۲۰۲۶-۱۱-۲۶)
        $monthRevenue = $canViewFinancials ? $this->monthSalonShare($monthStart) : null;
        $monthBookings = $this->bookingRepository->query()->where('booking_time', '>=', $monthStart)
            ->whereNotIn('status', ['cancelled'])->count();

        $customers = $this->userRepository->querySalonCustomers();
        $customersCount = (clone $customers)->count();
        $newCustomersMonth = (clone $customers)->where('users.created_at', '>=', $monthStart)->count();

        $specialistsCount = $this->specialistRepository->count();
        $specialistLimit = $salon?->specialistLimit();
        $onLeaveToday = \App\Models\Leave::query()->whereHas('specialist')->where('status', 'approved')
            ->whereDate('start_date', '<=', today())->whereDate('end_date', '>=', today())
            ->distinct()->count('specialist_id');

        $popularServices = $this->getPopularServicesWithTrend()
            ->take(4)
            ->map(fn (array $item) => (object) $item);

        $topSpecialists = $this->getActiveSpecialistsWithPerformance()
            ->take(5)
            ->map(fn (array $item) => (object) $item);

        $prepayMode = \App\Models\BookingPolicy::current()->wantsPrepayment() && ($salon?->acceptsOnlinePayments() ?? false);
        $isOwner = auth()->user()?->hasRole('super-admin')
            || ($salon !== null && auth()->user()?->salonRoleIn($salon->id) === 'owner');
        $setup = $salon ? SalonSetupChecklist::for($salon) : null;

        return [
            'salon' => $salon,
            'isOwner' => (bool) $isOwner,
            'setup' => $setup,
            'canViewFinancials' => $canViewFinancials,
            'monthName' => $month->format('F'),
            'todayBookings' => $todayBookings,
            'todayBookingsCount' => $todayBookings->count(),
            'todayWaiting' => $todayWaiting,
            'monthRevenue' => $monthRevenue,
            'monthBookings' => $monthBookings,
            'prepayMode' => $prepayMode,
            'customersCount' => $customersCount,
            'newCustomersMonth' => $newCustomersMonth,
            'specialistsCount' => $specialistsCount,
            'specialistLimit' => $specialistLimit,
            'onLeaveToday' => $onLeaveToday,
            'popularServices' => $popularServices,
            'topSpecialists' => $topSpecialists,
        ];
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
            'totalUsers' => $this->userRepository->querySalonMembers()->count(),
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

    /**
     * جمع سهم سالن از نوبت‌ها در این ماه از دفتر کیف پول سالن: «commission» (پیش‌پرداخت نوبت و جلسه‌ی دوره) و
     * «adjustment» منفی (برگشت کمیسیون نوبت لغوشده)؛ برداشت سالن جزو آن نیست. کیف پولی ساخته نمی‌شود.
     */
    private function monthSalonShare(\Illuminate\Support\Carbon $monthStart): int
    {
        $walletId = \App\Models\AdminWallet::query()->value('id');
        if ($walletId === null) {
            return 0;
        }

        return (int) round((float) \App\Models\AdminWalletTransaction::query()
            ->where('admin_wallet_id', $walletId)
            ->whereNotNull('booking_id')
            ->whereIn('type', ['commission', 'adjustment'])
            ->where('created_at', '>=', $monthStart)
            ->sum('amount'));
    }
}
