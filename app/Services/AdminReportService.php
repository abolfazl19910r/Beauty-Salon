<?php

namespace App\Services;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Specialist;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminReportService
{
    private array $jMonths = [
        'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
        'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند',
    ];

    /**
     * تبدیل start_date/end_date از input به Carbon و string.
     *
     * @param array $input ['start_date' => ..., 'end_date' => ...]
     * @param int $defaultSubDays تعداد روز پیش‌فرض اگه تاریخی نباشد
     * @return array{start: Carbon, end: Carbon, startDate: string, endDate: string}
     */
    public function parseDateRange(array $input, int $defaultSubDays = 30): array
    {
        $startDate = $input['start_date'] ?? now()->subDays($defaultSubDays)->format('Y-m-d');
        $endDate   = $input['end_date']   ?? now()->format('Y-m-d');

        if (! $this->validateDates($startDate, $endDate)) {
            $startDate = now()->subDays($defaultSubDays)->format('Y-m-d');
            $endDate   = now()->format('Y-m-d');
        }

        return [
            'start'     => Carbon::parse($startDate)->startOfDay(),
            'end'       => Carbon::parse($endDate)->endOfDay(),
            'startDate' => $startDate,
            'endDate'   => $endDate,
        ];
    }

    public function validateDates(string $startDate, string $endDate): bool
    {
        try {
            return Carbon::parse($startDate)->lte(Carbon::parse($endDate));
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getSummary(Carbon $start, Carbon $end): array
    {
        return [
            'total_revenue' => Booking::where('payment_status', 'paid')
                ->whereBetween('created_at', [$start, $end])
                ->sum('prepayment_amount'),

            'total_bookings' => Booking::whereBetween('created_at', [$start, $end])->count(),

            'completed_bookings' => Booking::where('status', 'completed')
                ->whereBetween('created_at', [$start, $end])
                ->count(),

            'cancelled_bookings' => Booking::where('status', 'cancelled')
                ->whereBetween('created_at', [$start, $end])
                ->count(),

            'pending_payments' => Booking::where('payment_status', 'unpaid')
                ->whereBetween('created_at', [$start, $end])
                ->sum('prepayment_amount'),

            'average_booking_value' => (float) (Booking::where('payment_status', 'paid')
                ->whereBetween('created_at', [$start, $end])
                ->avg('prepayment_amount') ?? 0),
        ];
    }

    public function getFinancialSummary(Carbon $start, Carbon $end): array
    {
        $base = Booking::whereBetween('created_at', [$start, $end]);

        return [
            'total_revenue'          => (clone $base)->where('payment_status', 'paid')->sum('prepayment_amount'),
            'total_bookings'         => (clone $base)->count(),
            'completed_bookings'     => (clone $base)->where('status', 'completed')->count(),
            'cancelled_bookings'     => (clone $base)->where('status', 'cancelled')->count(),
            'pending_payments'       => (clone $base)->where('payment_status', 'unpaid')->sum('prepayment_amount'),
            'average_booking_value'  => (float) ((clone $base)->where('payment_status', 'paid')->avg('prepayment_amount') ?? 0),
            'wallet_payments'        => (clone $base)->where('payment_method', 'wallet')->where('payment_status', 'paid')->sum('prepayment_amount'),
            'gateway_payments'       => (clone $base)->where('payment_method', '!=', 'wallet')->where('payment_status', 'paid')->sum('prepayment_amount'),
            'total_discounts'        => (clone $base)->sum('discount_amount'),
        ];
    }

    public function dailyRevenue(Carbon $start, Carbon $end): Collection
    {
        return Booking::where('payment_status', 'paid')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(prepayment_amount) as revenue'),
                DB::raw('COUNT(*) as total_bookings')
            )
            ->orderBy('date')
            ->get()
            ->map(function ($r) {
                $d = Carbon::parse($r->date);
                [$jy, $jm, $jd] = gregorian_to_jalali($d->year, $d->month, $d->day);

                return [
                    'label'    => $jd . ' ' . $this->jMonths[$jm - 1],
                    'date'     => $r->date,
                    'revenue'  => (int) $r->revenue,
                    'bookings' => (int) $r->total_bookings,
                ];
            });
    }

    public function weeklyRevenue(Carbon $start, Carbon $end): Collection
    {
        return Booking::where('payment_status', 'paid')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy(DB::raw('YEARWEEK(created_at)'))
            ->select(
                DB::raw('DATE(MIN(created_at)) as week_start'),
                DB::raw('SUM(prepayment_amount) as revenue'),
                DB::raw('COUNT(*) as total_bookings')
            )
            ->orderBy('week_start')
            ->get()
            ->map(function ($r) {
                $d = Carbon::parse($r->week_start);
                [$jy, $jm, $jd] = gregorian_to_jalali($d->year, $d->month, $d->day);

                return [
                    'label'    => $jd . ' ' . $this->jMonths[$jm - 1],
                    'revenue'  => (int) $r->revenue,
                    'bookings' => (int) $r->total_bookings,
                ];
            });
    }

    public function monthlyRevenue(Carbon $start, Carbon $end): Collection
    {
        return Booking::where('payment_status', 'paid')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy(DB::raw('YEAR(created_at)'), DB::raw('MONTH(created_at)'))
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(prepayment_amount) as revenue'),
                DB::raw('COUNT(*) as total_bookings')
            )
            ->orderBy('year')->orderBy('month')
            ->get()
            ->map(function ($r) {
                [$jy, $jm] = gregorian_to_jalali($r->year, $r->month, 1);

                return [
                    'label'    => $this->jMonths[$jm - 1] . ' ' . $jy,
                    'revenue'  => (int) $r->revenue,
                    'bookings' => (int) $r->total_bookings,
                ];
            });
    }

    public function getRevenueChartData(Carbon $start, Carbon $end, string $type): array
    {
        return match ($type) {
            'monthly' => $this->monthlyRevenue($start, $end)->toArray(),
            'weekly'  => $this->weeklyRevenue($start, $end)->toArray(),
            default   => $this->dailyRevenue($start, $end)->toArray(),
        };
    }

    public function monthlyBreakdown(Carbon $start, Carbon $end): Collection
    {
        return Booking::whereBetween('created_at', [$start, $end])
            ->groupBy(DB::raw('YEAR(created_at)'), DB::raw('MONTH(created_at)'))
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(*) as total_bookings'),
                DB::raw('SUM(CASE WHEN payment_status="paid" THEN prepayment_amount ELSE 0 END) as revenue'),
                DB::raw('COUNT(CASE WHEN status="cancelled" THEN 1 END) as cancelled_count'),
                DB::raw('COUNT(CASE WHEN status="completed" THEN 1 END) as completed_count')
            )
            ->orderBy('year')->orderBy('month')
            ->get()
            ->map(function ($r) {
                [$jy, $jm] = gregorian_to_jalali($r->year, $r->month, 1);

                return [
                    'label'           => $this->jMonths[$jm - 1] . ' ' . $jy,
                    'total_bookings'  => (int) $r->total_bookings,
                    'revenue'         => (int) $r->revenue,
                    'cancelled_count' => (int) $r->cancelled_count,
                    'completed_count' => (int) $r->completed_count,
                ];
            });
    }

    public function paymentBreakdown(): array
    {
        $total = Booking::where('payment_status', 'paid')->count();

        if ($total === 0) {
            return ['gateway' => 0, 'wallet' => 0, 'mixed' => 0, 'total' => 0];
        }

        $gateway = Booking::where('payment_status', 'paid')
            ->where('payment_method', '!=', 'wallet')->count();
        $wallet  = Booking::where('payment_status', 'paid')
            ->where('payment_method', 'wallet')->count();

        return [
            'gateway'          => $gateway,
            'gateway_percent'  => round($gateway / $total * 100, 1),
            'wallet'           => $wallet,
            'wallet_percent'   => round($wallet / $total * 100, 1),
            'total'            => $total,
        ];
    }

    public function serviceRevenue(Carbon $start, Carbon $end, int $limit = 8): Collection
    {
        return BeautyService::withCount(['bookings' => fn ($q) =>
        $q->whereBetween('created_at', [$start, $end])->where('status', '!=', 'cancelled')
        ])
            ->withSum(['bookings as revenue' => fn ($q) =>
            $q->where('payment_status', 'paid')->whereBetween('created_at', [$start, $end])
            ], 'prepayment_amount')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get()
            ->map(fn ($s) => [
                'name'     => $s->name,
                'bookings' => $s->bookings_count ?? 0,
                'revenue'  => (int) ($s->revenue ?? 0),
            ]);
    }

    public function getPopularServicesForIndex(Carbon $start, Carbon $end): Collection
    {
        return $this->popularServices($start, $end, limit: 5);
    }

    public function getSpecialistPerformanceForIndex(Carbon $start, Carbon $end): Collection
    {
        return $this->specialistPerformance($start, $end)->take(10);
    }

    public function popularServices(Carbon $start, Carbon $end, int $limit = 5): Collection
    {
        return BeautyService::withCount(['bookings' => fn ($q) =>
        $q->whereBetween('created_at', [$start, $end])->where('status', '!=', 'cancelled')
        ])
            ->withSum(['bookings as revenue' => fn ($q) =>
            $q->where('payment_status', 'paid')->whereBetween('created_at', [$start, $end])
            ], 'prepayment_amount')
            ->orderByDesc('bookings_count')
            ->limit($limit)
            ->get();
    }

    public function specialistPerformance(Carbon $start, Carbon $end): Collection
    {
        return Specialist::with(['bookings' => fn ($q) => $q->whereBetween('created_at', [$start, $end])])
            ->withCount(['bookings as total_bookings' => fn ($q) => $q->whereBetween('created_at', [$start, $end])])
            ->withSum(['bookings as total_revenue' => fn ($q) =>
            $q->whereBetween('created_at', [$start, $end])->where('payment_status', 'paid')
            ], 'prepayment_amount')
            ->orderByDesc('total_bookings')
            ->get()
            ->map(function ($s) {
                $commissionRate  = $s->getEffectiveCommissionRate();
                $totalRevenue    = (float) ($s->total_revenue ?? 0);
                $specialistShare = $totalRevenue * (1 - $commissionRate / 100);

                return [
                    'id'                      => $s->id,
                    'name'                    => $s->name,
                    'total_bookings'          => $s->total_bookings ?? 0,
                    'total_revenue'           => $totalRevenue,
                    'commission_rate'         => $commissionRate,
                    'specialist_share'        => round($specialistShare),
                    'booking_completion_rate' => $this->calcCompletionRate($s->bookings),
                    'customer_return_rate'    => $this->calcReturnRate($s->bookings),
                    'performance_score'       => $this->calcSpecialistScore($s),
                ];
            });
    }

    public function customerSatisfaction(Carbon $start, Carbon $end): Collection
    {
        return Booking::whereBetween('created_at', [$start, $end])
            ->whereNotNull('rating')
            ->select(
                'specialist_id',
                DB::raw('ROUND(AVG(rating), 2) as average_rating'),
                DB::raw('COUNT(*) as total_ratings'),
                DB::raw('COUNT(CASE WHEN rating >= 4 THEN 1 END) as positive_ratings')
            )
            ->groupBy('specialist_id')
            ->with('specialist:id,name')
            ->get()
            ->map(fn ($item) => [
                'specialist_name'   => $item->specialist->name ?? '—',
                'average_rating'    => $item->average_rating,
                'total_ratings'     => $item->total_ratings,
                'satisfaction_rate' => $item->total_ratings > 0
                    ? round(($item->positive_ratings / $item->total_ratings) * 100, 1)
                    : 0,
            ]);
    }

    public function buildExportData(Carbon $start, Carbon $end, string $type): array
    {
        return [
            'summary'     => $this->getFinancialSummary($start, $end),
            'specialists' => Specialist::withCount(['bookings as total_bookings' => fn ($q) => $q->whereBetween('created_at', [$start, $end])])
                ->withSum(['bookings as total_revenue' => fn ($q) =>
                $q->whereBetween('created_at', [$start, $end])->where('payment_status', 'paid')
                ], 'prepayment_amount')
                ->orderByDesc('total_bookings')
                ->get(),
            'services'    => BeautyService::withCount(['bookings' => fn ($q) => $q->whereBetween('created_at', [$start, $end])])
                ->withSum(['bookings as revenue' => fn ($q) =>
                $q->where('payment_status', 'paid')->whereBetween('created_at', [$start, $end])
                ], 'prepayment_amount')
                ->orderByDesc('bookings_count')
                ->limit(10)
                ->get(),
            'rows'        => $this->getRowsForType($start, $end, $type),
        ];
    }

    private function getRowsForType(Carbon $start, Carbon $end, string $type): Collection
    {
        return match ($type) {
            'monthly' => $this->monthlyRevenue($start, $end),
            'weekly'  => $this->weeklyRevenue($start, $end),
            default   => $this->dailyRevenue($start, $end),
        };
    }

    public function calcFinancialTrends(string $startDate, string $endDate): array
    {
        $s       = Carbon::parse($startDate);
        $e       = Carbon::parse($endDate);
        $days    = $s->diffInDays($e);
        $prevEnd = $s->copy()->subDay();
        $prevSt  = $prevEnd->copy()->subDays($days);

        $curRev  = Booking::where('payment_status', 'paid')->whereBetween('created_at', [$s, $e])->sum('prepayment_amount');
        $prevRev = Booking::where('payment_status', 'paid')->whereBetween('created_at', [$prevSt, $prevEnd])->sum('prepayment_amount');

        return [
            'revenue_change'  => $prevRev > 0
                ? round(($curRev - $prevRev) / $prevRev * 100, 2)
                : ($curRev > 0 ? 100 : 0),
            'previous_period' => ['start' => $prevSt->toDateString(), 'end' => $prevEnd->toDateString(), 'revenue' => $prevRev],
            'current_period'  => ['start' => $startDate, 'end' => $endDate, 'revenue' => $curRev],
        ];
    }

    public function calcCompletionRate($bookings): float
    {
        $total = $bookings->count();

        return $total > 0
            ? round($bookings->where('status', 'completed')->count() / $total * 100, 1)
            : 0;
    }

    public function calcReturnRate($bookings): float
    {
        $customers = $bookings->pluck('user_id')->unique();
        if ($customers->count() === 0) {
            return 0;
        }

        $returnCount = $bookings->groupBy('user_id')->filter(fn ($b) => $b->count() > 1)->count();

        return round($returnCount / $customers->count() * 100, 1);
    }

    public function calcSpecialistScore(Specialist $specialist): float
    {
        $c = $this->calcCompletionRate($specialist->bookings);
        $r = $this->calcReturnRate($specialist->bookings);
        $b = min(($specialist->total_bookings ?? 0) / 100, 1) * 20;
        $v = min(($specialist->total_revenue ?? 0) / 1_000_000, 1) * 20;

        return round($c * 0.3 + $r * 0.3 + $b + $v, 2);
    }
}
