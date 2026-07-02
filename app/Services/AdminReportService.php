<?php

namespace App\Services;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Specialist;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class AdminReportService
{
    private array $jMonths = [
        'فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور',
        'مهر','آبان','آذر','دی','بهمن','اسفند',
    ];

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

    public function getRevenueChartData(Carbon $start, Carbon $end, string $type): array
    {
        $base = Booking::where('payment_status', 'paid')
            ->whereBetween('created_at', [$start, $end]);

        return match ($type) {
            'monthly' => $this->monthlyChartData(clone $base),
            'weekly'  => $this->weeklyChartData(clone $base),
            default   => $this->dailyChartData(clone $base),
        };
    }

    private function monthlyChartData($query): array
    {
        return $query
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
            })
            ->toArray();
    }

    private function weeklyChartData($query): array
    {
        return $query
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
            })
            ->toArray();
    }

    private function dailyChartData($query): array
    {
        return $query
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
                    'revenue'  => (int) $r->revenue,
                    'bookings' => (int) $r->total_bookings,
                ];
            })
            ->toArray();
    }

    public function getPopularServices(Carbon $start, Carbon $end, int $limit = 10)
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

    public function getSpecialistsPerformance(Carbon $start, Carbon $end)
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
                $totalRevenue    = $s->total_revenue ?? 0;
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

    public function getCustomerSatisfaction(Carbon $start, Carbon $end)
    {
        return Booking::whereBetween('created_at', [$start, $end])
            ->whereNotNull('rating')
            ->select(
                'specialist_id',
                DB::raw('ROUND(AVG(rating),2) as average_rating'),
                DB::raw('COUNT(*) as total_ratings'),
                DB::raw('COUNT(CASE WHEN rating >= 4 THEN 1 END) as positive_ratings')
            )
            ->groupBy('specialist_id')
            ->with('specialist:id,name')
            ->get()
            ->map(fn ($item) => [
                'specialist_name'  => $item->specialist->name ?? '—',
                'average_rating'   => $item->average_rating,
                'total_ratings'    => $item->total_ratings,
                'satisfaction_rate'=> $item->total_ratings > 0
                    ? round(($item->positive_ratings / $item->total_ratings) * 100, 1)
                    : 0,
            ]);
    }

    public function buildExportData(Carbon $start, Carbon $end, string $type): array
    {
        $summary = [
            'total_revenue'      => Booking::where('payment_status', 'paid')->whereBetween('created_at', [$start, $end])->sum('prepayment_amount'),
            'total_bookings'     => Booking::whereBetween('created_at', [$start, $end])->count(),
            'completed_bookings' => Booking::where('status', 'completed')->whereBetween('created_at', [$start, $end])->count(),
            'cancelled_bookings' => Booking::where('status', 'cancelled')->whereBetween('created_at', [$start, $end])->count(),
        ];

        $specialists = Specialist::withCount(['bookings as total_bookings' => fn ($q) => $q->whereBetween('created_at', [$start, $end])])
            ->withSum(['bookings as total_revenue' => fn ($q) => $q->whereBetween('created_at', [$start, $end])->where('payment_status', 'paid')], 'prepayment_amount')
            ->orderByDesc('total_bookings')
            ->get();

        $services = BeautyService::withCount(['bookings' => fn ($q) => $q->whereBetween('created_at', [$start, $end])])
            ->withSum(['bookings as revenue' => fn ($q) => $q->where('payment_status', 'paid')->whereBetween('created_at', [$start, $end])], 'prepayment_amount')
            ->orderByDesc('bookings_count')
            ->limit(10)
            ->get();

        $rows = $this->getRevenueRows($start, $end, $type);

        return compact('summary', 'specialists', 'services', 'rows');
    }

    private function getRevenueRows(Carbon $start, Carbon $end, string $type)
    {
        $base = Booking::where('payment_status', 'paid')->whereBetween('created_at', [$start, $end]);

        return match ($type) {
            'monthly' => (clone $base)
                ->groupBy(DB::raw('YEAR(created_at)'), DB::raw('MONTH(created_at)'))
                ->select(DB::raw('YEAR(created_at) as year'), DB::raw('MONTH(created_at) as month'), DB::raw('COUNT(*) as total_bookings'), DB::raw('SUM(prepayment_amount) as revenue'))
                ->orderBy('year')->orderBy('month')->get(),

            'weekly' => (clone $base)
                ->groupBy(DB::raw('YEARWEEK(created_at)'))
                ->select(DB::raw('DATE(MIN(created_at)) as week_start'), DB::raw('COUNT(*) as total_bookings'), DB::raw('SUM(prepayment_amount) as revenue'))
                ->orderBy('week_start')->get(),

            default => (clone $base)
                ->groupBy(DB::raw('DATE(created_at)'))
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as total_bookings'), DB::raw('SUM(prepayment_amount) as revenue'))
                ->orderBy('date')->get(),
        };
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
        $v = min(($specialist->total_revenue ?? 0) / 1000000, 1) * 20;

        return round($c * 0.3 + $r * 0.3 + $b + $v, 2);
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

    public function validateDates(string $startDate, string $endDate): bool
    {
        try {
            return Carbon::parse($startDate)->lte(Carbon::parse($endDate));
        } catch (\Exception $e) {
            return false;
        }
    }
}
