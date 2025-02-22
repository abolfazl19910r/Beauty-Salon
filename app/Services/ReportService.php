<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BeautyService;
use App\Models\ScheduledReport;
use App\Models\Specialist;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReportService
{
    protected ReportCacheService $cacheService;
    protected int $cacheDuration = 3600;
    public function __construct(ReportCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function getRevenueReport($type, $startDate = null, $endDate = null, $compareWith = null)
    {
        $params = compact('type', 'startDate', 'endDate', 'compareWith');

        return $this->cacheService->remember("revenue_report", $params, function () use ($type, $startDate, $endDate, $compareWith) {
            $query = Booking::where('payment_status', 'paid');

            if ($startDate && $endDate) {
                $query->whereBetween('created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ]);
            }

            $result = match($type) {
                'daily' => $this->getDailyRevenue($query),
                'weekly' => $this->getWeeklyRevenue($query),
                'monthly' => $this->getMonthlyRevenue($query),
                default => []
            };

            if ($compareWith) {
                $previousPeriodLength = Carbon::parse($endDate)->diffInDays(Carbon::parse($startDate));
                $previousStartDate = Carbon::parse($startDate)->subDays($previousPeriodLength);
                $previousEndDate = Carbon::parse($startDate)->subDay();

                $compareQuery = Booking::where('payment_status', 'paid')
                    ->whereBetween('created_at', [$previousStartDate, $previousEndDate]);

                $compareResult = match($type) {
                    'daily' => $this->getDailyRevenue($compareQuery),
                    'weekly' => $this->getWeeklyRevenue($compareQuery),
                    'monthly' => $this->getMonthlyRevenue($compareQuery),
                    default => []
                };

                return [
                    'current_period' => $result,
                    'previous_period' => $compareResult,
                    'comparison' => $this->calculateComparison($result, $compareResult)
                ];
            }

            return $result;
        });
    }

    public function getSpecialistPerformance($startDate = null, $endDate = null)
    {
        $params = compact('startDate', 'endDate');

        return $this->cacheService->remember("specialist_performance", $params, function () use ($startDate, $endDate) {
            $query = Specialist::with(['bookings' => function($query) use ($startDate, $endDate) {
                if ($startDate && $endDate) {
                    $query->whereBetween('created_at', [
                        Carbon::parse($startDate)->startOfDay(),
                        Carbon::parse($endDate)->endOfDay()
                    ]);
                }
            }])
                ->withCount(['bookings as total_bookings'])
                ->withSum('bookings', 'prepayment_amount')
                ->withAvg('bookings', 'prepayment_amount');

            return $query->get()->map(function($specialist) {
                return [
                    'id' => $specialist->id,
                    'name' => $specialist->name,
                    'total_bookings' => $specialist->total_bookings,
                    'total_revenue' => $specialist->bookings_sum_prepayment_amount,
                    'average_booking_value' => $specialist->bookings_avg_prepayment_amount,
                    'booking_completion_rate' => $this->calculateCompletionRate($specialist->bookings),
                    'customer_return_rate' => $this->calculateReturnRate($specialist->bookings),
                    'performance_score' => $this->calculatePerformanceScore($specialist),
                    'top_services' => $this->getSpecialistTopServices($specialist->id)
                ];
            });
        });
    }

    protected function calculatePerformanceScore($specialist): float
    {
        $completionRate = $this->calculateCompletionRate($specialist->bookings);
        $returnRate = $this->calculateReturnRate($specialist->bookings);
        $bookingCount = $specialist->total_bookings;
        $revenue = $specialist->bookings_sum_prepayment_amount;

        $score = ($completionRate * 0.3) +
            ($returnRate * 0.3) +
            (min($bookingCount / 100, 1) * 20) +
            (min($revenue / 1000000, 1) * 20);

        return round($score, 2);
    }

    protected function getSpecialistTopServices($specialistId)
    {
        return Booking::where('specialist_id', $specialistId)
            ->select('service_id', DB::raw('COUNT(*) as count'))
            ->with('service:id,name')
            ->groupBy('service_id')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(function($booking) {
                return [
                    'service_name' => $booking->service->name,
                    'count' => $booking->count
                ];
            });
    }

    protected function getDailyRevenue($query)
    {
        return $query->groupBy(DB::raw('DATE(created_at)'))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total_bookings'),
                DB::raw('SUM(prepayment_amount) as revenue')
            )
            ->orderBy('date')
            ->get();
    }

    protected function getWeeklyRevenue($query)
    {
        return $query->groupBy(DB::raw('YEAR-WEEK(created_at)'))
            ->select(
                DB::raw('DATE(MIN(created_at)) as week_start'),
                DB::raw('DATE(MAX(created_at)) as week_end'),
                DB::raw('COUNT(*) as total_bookings'),
                DB::raw('SUM(prepayment_amount) as revenue'),
                DB::raw('AVG(prepayment_amount) as average_booking_value')
            )
            ->orderBy('week_start')
            ->get();
    }

    protected function getMonthlyRevenue($query)
    {
        return $query->groupBy(DB::raw('YEAR(created_at)'), DB::raw('MONTH(created_at)'))
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(*) as total_bookings'),
                DB::raw('SUM(prepayment_amount) as revenue'),
                DB::raw('AVG(prepayment_amount) as average_booking_value')
            )
            ->orderBy('year')
            ->orderBy('month')
            ->get();
    }

    protected function calculateComparison($current, $previous): array
    {
        $currentTotal = collect($current)->sum('revenue');
        $previousTotal = collect($previous)->sum('revenue');

        $change = $previousTotal > 0
            ? (($currentTotal - $previousTotal) / $previousTotal) * 100
            : 100;

        return [
            'percentage_change' => round($change, 2),
            'value_change' => $currentTotal - $previousTotal
        ];
    }

    public function scheduleReport($userId, $reportType, array $params = [])
    {
        return ScheduledReport::create([
            'user_id' => $userId,
            'report_type' => $reportType,
            'parameters' => $params,
            'frequency' => $params['frequency'] ?? 'daily',
            'next_run' => $this->calculateNextRun($params['frequency'] ?? 'daily'),
            'recipients' => $params['recipients'] ?? [],
        ]);
    }

    protected function calculateNextRun($frequency)
    {
        return match($frequency) {
            'weekly' => Carbon::now()->addWeek()->startOfWeek(),
            'monthly' => Carbon::now()->addMonth()->startOfMonth(),
            default => Carbon::tomorrow()
        };
    }

    protected function calculateCompletionRate($bookings): float|int
    {
        $total = $bookings->count();
        if ($total === 0) return 0;

        $completed = $bookings->where('status', 'completed')->count();
        return round(($completed / $total) * 100, 2);
    }

    protected function calculateReturnRate($bookings): float|int
    {
        $customers = $bookings->pluck('user_id')->unique();
        $returnCustomers = $bookings
            ->groupBy('user_id')
            ->filter(function($customerBookings) {
                return $customerBookings->count() > 1;
            })
            ->count();

        return $customers->count() > 0
            ? round(($returnCustomers / $customers->count()) * 100, 2)
            : 0;
    }
}
