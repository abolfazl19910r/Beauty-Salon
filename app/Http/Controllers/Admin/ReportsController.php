<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BeautyService;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\ReportsExport;
use App\Services\ChartExportService;
use App\Services\ReportService;
use App\Models\ScheduledReport;
use App\Models\UserReportSetting;

class ReportsController extends Controller
{
    protected ReportService $reportService;
    protected ChartExportService $chartExportService;

    public function __construct(ReportService $reportService, ChartExportService $chartExportService)
    {
        $this->reportService = $reportService;
        $this->chartExportService = $chartExportService;
    }

    public function index()
    {
        $userSettings = UserReportSetting::where('user_id', auth()->id())->first();

        return view('admin.reports.index', [
            'defaultSettings' => $userSettings?->settings ?? []
        ]);
    }

    public function dailyRevenue(Request $request)
    {
        $cacheKey = "daily_revenue_{$request->start_date}_{$request->end_date}";

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($request) {
            $startDate = $request->input('start_date', now()->subDays(30));
            $endDate = $request->input('end_date', now());

            return Booking::where('payment_status', 'paid')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy(DB::raw('DATE(created_at)'))
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as total_bookings'),
                    DB::raw('SUM(prepayment_amount) as revenue')
                )
                ->orderBy('date')
                ->get();
        });
    }

    public function weeklyRevenue()
    {
        $weeklyRevenue = Booking::where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subWeeks(12))
            ->groupBy(DB::raw('YEARWEEK(created_at)'))
            ->select(
                DB::raw('DATE(MIN(created_at)) as week_start'),
                DB::raw('DATE(MAX(created_at)) as week_end'),
                DB::raw('COUNT(*) as total_bookings'),
                DB::raw('SUM(prepayment_amount) as revenue'),
                DB::raw('AVG(prepayment_amount) as average_booking_value')
            )
            ->orderBy('week_start')
            ->get();

        Log::info('Weekly revenue report generated', ['count' => $weeklyRevenue->count()]);
        return response()->json($weeklyRevenue);
    }

    public function monthlyRevenue()
    {
        $monthlyRevenue = Booking::where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subYear())
            ->groupBy(DB::raw('YEAR(created_at)'), DB::raw('MONTH(created_at)'))
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

        Log::info('Monthly revenue report generated', ['count' => $monthlyRevenue->count()]);
        return response()->json($monthlyRevenue);
    }

    public function specialistPerformance()
    {
        $specialists = Specialist::with(['bookings' => function($query) {
            $query->where('created_at', '>=', now()->subMonth());
        }])
            ->withCount(['bookings as total_bookings'])
            ->withSum('bookings', 'prepayment_amount')
            ->withAvg('bookings', 'prepayment_amount')
            ->get()
            ->map(function($specialist) {
                return [
                    'id' => $specialist->id,
                    'name' => $specialist->name,
                    'total_bookings' => $specialist->total_bookings,
                    'total_revenue' => $specialist->bookings_sum_prepayment_amount,
                    'average_booking_value' => $specialist->bookings_avg_prepayment_amount,
                    'booking_completion_rate' => $this->calculateCompletionRate($specialist->bookings),
                    'customer_return_rate' => $this->calculateReturnRate($specialist->bookings)
                ];
            });

        return response()->json($specialists);
    }

    public function customerSatisfaction()
    {
        $satisfaction = Booking::where('created_at', '>=', now()->subMonths(3))
            ->whereNotNull('rating')
            ->select(
                'specialist_id',
                DB::raw('AVG(rating) as average_rating'),
                DB::raw('COUNT(*) as total_ratings'),
                DB::raw('COUNT(CASE WHEN rating >= 4 THEN 1 END) as positive_ratings')
            )
            ->groupBy('specialist_id')
            ->with('specialist:id,name')
            ->get()
            ->map(function($item) {
                return [
                    'specialist_name' => $item->specialist->name,
                    'average_rating' => round($item->average_rating, 2),
                    'total_ratings' => $item->total_ratings,
                    'satisfaction_rate' => round(($item->positive_ratings / $item->total_ratings) * 100, 2)
                ];
            });

        return response()->json($satisfaction);
    }

    public function popularServices()
    {
        $services = BeautyService::withCount(['bookings' => function($query) {
            $query->where('created_at', '>=', now()->subMonths(3))
                ->where('status', '!=', 'cancelled');
        }])
            ->withSum(['bookings' => function($query) {
                $query->where('payment_status', 'paid')
                    ->where('created_at', '>=', now()->subMonths(3));
            }], 'prepayment_amount')
            ->orderByDesc('bookings_count')
            ->limit(10)
            ->get()
            ->map(function($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'bookings_count' => $service->bookings_count,
                    'revenue' => $service->bookings_sum_prepayment_amount,
                    'trend' => $this->calculateServiceTrend($service->id)
                ];
            });

        Log::info('Popular services report generated', ['count' => $services->count()]);
        return response()->json($services);
    }

    public function exportReport(Request $request)
    {
        $type = $request->input('type', 'daily');
        $format = $request->input('format', 'excel');

        $data = match($type) {
            'daily' => $this->dailyRevenue($request)->original,
            'weekly' => $this->weeklyRevenue($request)->original,
            'monthly' => $this->monthlyRevenue($request)->original,
            default => []
        };

        if ($format === 'excel') {
            return Excel::download(new ReportsExport($data, $type), "report-{$type}.xlsx");
        }

        if ($format === 'pdf') {
            $pdf = PDF::loadView('exports.report', [
                'data' => $data,
                'type' => $type,
                'headings' => (new ReportsExport($data, $type))->headings()
            ]);
            return $pdf->download("report-{$type}.pdf");
        }

        return response()->json($data);
    }

    public function exportChart(Request $request)
    {
        $chart = $this->chartExportService->generateChart(
            $request->type,
            $request->data,
            $request->options ?? []
        );

        return response()->download($chart);
    }

    public function scheduleReport(Request $request)
    {
        $report = $this->reportService->scheduleReport(
            auth()->id(),
            $request->type,
            $request->all()
        );

        return response()->json($report);
    }

    public function saveUserSettings(Request $request)
    {
        $settings = UserReportSetting::updateOrCreate(
            ['user_id' => auth()->id()],
            ['settings' => $request->settings]
        );

        return response()->json($settings);
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

    protected function calculateServiceTrend($serviceId): float|int
    {
        $previousMonth = Booking::where('service_id', $serviceId)
            ->whereBetween('created_at', [now()->subMonths(2), now()->subMonth()])
            ->count();

        $currentMonth = Booking::where('service_id', $serviceId)
            ->whereBetween('created_at', [now()->subMonth(), now()])
            ->count();

        if ($previousMonth == 0) return 100;

        $change = (($currentMonth - $previousMonth) / $previousMonth) * 100;
        return round($change, 2);
    }

    private function getFinancialSummary(): array
    {
        return [
            'total_revenue' => Booking::where('payment_status', 'paid')->sum('prepayment_amount'),
            'average_booking_value' => Booking::where('payment_status', 'paid')->avg('prepayment_amount'),
            'pending_payments' => Booking::where('payment_status', 'unpaid')->sum('prepayment_amount'),
            'refunded_amount' => Booking::where('status', 'cancelled')->sum('prepayment_amount')
        ];
    }

    private function getMonthlyBreakdown()
    {
        return Booking::where('payment_status', 'paid')
            ->where('created_at', '>=', now()->startOfYear())
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(prepayment_amount) as revenue'),
                DB::raw('COUNT(*) as bookings'),
                DB::raw('AVG(prepayment_amount) as average_booking_value')
            )
            ->get();
    }

    private function getServiceRevenue()
    {
        return BeautyService::withSum('bookings', 'prepayment_amount')
            ->withCount('bookings')
            ->orderByDesc('bookings_sum_prepayment_amount')
            ->get()
            ->map(function($service) {
                return [
                    'name' => $service->name,
                    'revenue' => $service->bookings_sum_prepayment_amount,
                    'bookings' => $service->bookings_count,
                    'average_revenue' => $service->bookings_count > 0
                        ? $service->bookings_sum_prepayment_amount / $service->bookings_count
                        : 0
                ];
            });
    }

    private function getPaymentMethodsBreakdown()
    {
        return Booking::where('payment_status', 'paid')
            ->groupBy('payment_method')
            ->select(
                'payment_method',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(prepayment_amount) as total_amount')
            )
            ->get();
    }
}
