<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BeautyService;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Http\JsonResponse;
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

class AdminReportsController extends Controller
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

    public function weeklyRevenue(Request $request): JsonResponse
    {
        try {
            $startDate = $request->input('start_date', now()->subWeeks(12)->format('Y-m-d'));
            $endDate = $request->input('end_date', now()->format('Y-m-d'));

            if (!$this->validateDates($startDate, $endDate)) {
                return $this->errorResponse('تاریخ‌های ارسالی معتبر نیستند');
            }

            $cacheKey = "weekly_revenue_{$startDate}_{$endDate}";

            $data = Cache::remember($cacheKey, now()->addHours(1), function () use ($startDate, $endDate) {
                return Booking::where('payment_status', 'paid')
                    ->whereBetween('created_at', [$startDate, $endDate])
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
            });

            Log::info('Weekly revenue report generated', ['count' => $data->count()]);

            return $this->successResponse($data, null, 200, [
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate,
                    'type' => $request->input('type', 'weekly')
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating weekly revenue report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('خطا در تولید گزارش درآمد هفتگی: ' . $e->getMessage(), 500);
        }
    }

    public function monthlyRevenue(Request $request): JsonResponse
    {
        try {
            $startDate = $request->input('start_date', now()->subYear()->format('Y-m-d'));
            $endDate = $request->input('end_date', now()->format('Y-m-d'));

            if (!$this->validateDates($startDate, $endDate)) {
                return $this->errorResponse('تاریخ‌های ارسالی معتبر نیستند');
            }

            $cacheKey = "monthly_revenue_{$startDate}_{$endDate}";

            $data = Cache::remember($cacheKey, now()->addHours(2), function () use ($startDate, $endDate) {
                return Booking::where('payment_status', 'paid')
                    ->whereBetween('created_at', [$startDate, $endDate])
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
            });

            Log::info('Monthly revenue report generated', ['count' => $data->count()]);

            return $this->successResponse($data, null, 200, [
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate,
                    'type' => $request->input('type', 'monthly')
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating monthly revenue report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('خطا در تولید گزارش درآمد ماهانه: ' . $e->getMessage(), 500);
        }
    }

    public function specialistPerformance(Request $request): JsonResponse
    {
        try {
            $startDate = $request->input('start_date', now()->subMonth()->format('Y-m-d'));
            $endDate = $request->input('end_date', now()->format('Y-m-d'));

            if (!$this->validateDates($startDate, $endDate)) {
                return $this->errorResponse('تاریخ‌های ارسالی معتبر نیستند');
            }

            $specialists = Specialist::with(['bookings' => function($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }])
                ->withCount(['bookings as total_bookings' => function($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                }])
                ->withSum(['bookings as total_revenue' => function($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate])
                        ->where('payment_status', 'paid');
                }], 'prepayment_amount')
                ->withAvg(['bookings as average_booking_value' => function($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate])
                        ->where('payment_status', 'paid');
                }], 'prepayment_amount')
                ->get()
                ->map(function($specialist) {
                    return [
                        'id' => $specialist->id,
                        'name' => $specialist->name,
                        'total_bookings' => $specialist->total_bookings ?? 0,
                        'total_revenue' => $specialist->total_revenue ?? 0,
                        'average_booking_value' => $specialist->average_booking_value ?? 0,
                        'booking_completion_rate' => $this->calculateCompletionRate($specialist->bookings),
                        'customer_return_rate' => $this->calculateReturnRate($specialist->bookings)
                    ];
                });

            return $this->successResponse(['specialists' => $specialists], null, 200, [
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate,
                    'type' => $request->input('type', 'monthly')
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating specialist performance report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('خطا در تولید گزارش عملکرد متخصصین: ' . $e->getMessage(), 500);
        }
    }

    public function customerSatisfaction(Request $request): JsonResponse
    {
        try {
            $startDate = $request->input('start_date', now()->subMonths(3)->format('Y-m-d'));
            $endDate = $request->input('end_date', now()->format('Y-m-d'));

            if (!$this->validateDates($startDate, $endDate)) {
                return $this->errorResponse('تاریخ‌های ارسالی معتبر نیستند');
            }

            $satisfaction = Booking::whereBetween('created_at', [$startDate, $endDate])
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
                        'satisfaction_rate' => $item->total_ratings > 0
                            ? round(($item->positive_ratings / $item->total_ratings) * 100, 2)
                            : 0
                    ];
                });

            return $this->successResponse(['satisfaction' => $satisfaction], null, 200, [
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate,
                    'type' => $request->input('type', 'monthly')
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating customer satisfaction report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('خطا در تولید گزارش رضایت مشتریان: ' . $e->getMessage(), 500);
        }
    }

    public function popularServices(Request $request): JsonResponse
    {
        try {
            $startDate = $request->input('start_date', now()->subMonths(3)->format('Y-m-d'));
            $endDate = $request->input('end_date', now()->format('Y-m-d'));

            if (!$this->validateDates($startDate, $endDate)) {
                return $this->errorResponse('تاریخ‌های ارسالی معتبر نیستند');
            }

            $services = BeautyService::withCount(['bookings' => function($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate])
                    ->where('status', '!=', 'cancelled');
            }])
                ->withSum(['bookings' => function($query) use ($startDate, $endDate) {
                    $query->where('payment_status', 'paid')
                        ->whereBetween('created_at', [$startDate, $endDate]);
                }], 'prepayment_amount')
                ->orderByDesc('bookings_count')
                ->limit(10)
                ->get()
                ->map(function($service) use ($startDate, $endDate) {
                    return [
                        'id' => $service->id,
                        'name' => $service->name,
                        'bookings_count' => $service->bookings_count ?? 0,
                        'revenue' => $service->bookings_sum_prepayment_amount ?? 0,
                        'trend' => $this->calculateServiceTrend($service->id, $startDate, $endDate)
                    ];
                });

            return $this->successResponse(['services' => $services], null, 200, [
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate,
                    'type' => $request->input('type', 'monthly')
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating popular services report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('خطا در تولید گزارش خدمات محبوب: ' . $e->getMessage(), 500);
        }
    }

    public function exportReport(Request $request): \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\BinaryFileResponse|JsonResponse
    {
        try {
            $type = $request->input('type', 'daily');
            $format = $request->input('format', 'excel');
            $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
            $endDate = $request->input('end_date', now()->format('Y-m-d'));

            if (!$this->validateDates($startDate, $endDate)) {
                return $this->errorResponse('تاریخ‌های ارسالی معتبر نیستند');
            }

            $data = null;
            switch($type) {
                case 'daily':
                    $result = $this->dailyRevenue($request);
                    $data = $result->getData(true)['data'] ?? [];
                    break;
                case 'weekly':
                    $result = $this->weeklyRevenue($request);
                    $data = $result->getData(true)['data'] ?? [];
                    break;
                case 'monthly':
                    $result = $this->monthlyRevenue($request);
                    $data = $result->getData(true)['data'] ?? [];
                    break;
                default:
                    return $this->errorResponse('نوع گزارش معتبر نیست');
            }

            if ($format === 'excel') {
                return Excel::download(new ReportsExport($data, $type), "report-{$type}.xlsx");
            }

            if ($format === 'pdf') {
                $pdf = PDF::loadView('exports.report', [
                    'data' => $data,
                    'type' => $type,
                    'headings' => (new ReportsExport($data, $type))->headings(),
                    'period' => [
                        'start' => $startDate,
                        'end' => $endDate
                    ]
                ]);
                return $pdf->download("report-{$type}.pdf");
            }

            return $this->successResponse($data);
        } catch (\Exception $e) {
            Log::error('Error exporting report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('خطا در خروجی گرفتن از گزارش: ' . $e->getMessage(), 500);
        }
    }

    public function exportChart(Request $request)
    {
        try {
            if (!$request->has('type') || !$request->has('data')) {
                return $this->errorResponse('پارامترهای مورد نیاز ارسال نشده‌اند');
            }

            $chart = $this->chartExportService->generateChart(
                $request->type,
                $request->data,
                $request->options ?? []
            );

            return response()->download($chart);
        } catch (\Exception $e) {
            Log::error('Error exporting chart', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('خطا در خروجی گرفتن از نمودار: ' . $e->getMessage(), 500);
        }
    }

    public function scheduleReport(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'type' => 'required|string|in:daily,weekly,monthly,specialists,customers,financial',
                'frequency' => 'required|string|in:daily,weekly,monthly',
                'recipients' => 'sometimes|array',
                'recipients.*' => 'email'
            ]);

            $report = $this->reportService->scheduleReport(
                auth()->id(),
                $request->type,
                $request->all()
            );

            return $this->successResponse($report, 'گزارش با موفقیت زمانبندی شد');
        } catch (\Exception $e) {
            Log::error('Error scheduling report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('خطا در زمانبندی گزارش: ' . $e->getMessage(), 500);
        }
    }

    public function saveUserSettings(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'settings' => 'required|array'
            ]);

            $settings = UserReportSetting::updateOrCreate(
                ['user_id' => auth()->id()],
                ['settings' => $request->settings]
            );

            return $this->successResponse($settings, 'تنظیمات با موفقیت ذخیره شد');
        } catch (\Exception $e) {
            Log::error('Error saving user settings', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('خطا در ذخیره تنظیمات: ' . $e->getMessage(), 500);
        }
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

    protected function calculateServiceTrend($serviceId, $startDate, $endDate): float|int
    {
        $interval = Carbon::parse($startDate)->diffInDays($endDate);

        $previousStart = Carbon::parse($startDate)->subDays($interval);
        $previousEnd = Carbon::parse($startDate);

        $previousCount = Booking::where('service_id', $serviceId)
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->count();

        $currentCount = Booking::where('service_id', $serviceId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        if ($previousCount == 0) return 100;

        $change = (($currentCount - $previousCount) / $previousCount) * 100;
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
        return \App\Models\Payment::where('status', 'completed')
            ->select(
                DB::raw("COALESCE(gateway_reference, 'نامشخص') as payment_method"),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->groupBy('payment_method')
            ->get();
    }

    public function servicesReport(Request $request)
    {
        try {
            $startDate = $request->input('start_date', now()->subMonths(3)->format('Y-m-d'));
            $endDate = $request->input('end_date', now()->format('Y-m-d'));

            if (!$this->validateDates($startDate, $endDate)) {
                return $this->errorResponse('تاریخ‌های ارسالی معتبر نیستند');
            }

            $servicesData = BeautyService::withCount(['bookings' => function($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }])
                ->withSum(['bookings' => function($query) use ($startDate, $endDate) {
                    $query->where('payment_status', 'paid')
                        ->whereBetween('created_at', [$startDate, $endDate]);
                }], 'prepayment_amount')
                ->get();

            if ($request->expectsJson()) {
                return $this->successResponse(['services' => $servicesData], null, 200, [
                    'period' => [
                        'start' => $startDate,
                        'end' => $endDate
                    ]
                ]);
            }

            return view('admin.reports.services', compact('servicesData'));
        } catch (\Exception $e) {
            Log::error('Error generating services report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return $this->errorResponse('خطا در تولید گزارش خدمات: ' . $e->getMessage(), 500);
            }

            return back()->with('error', 'خطا در تولید گزارش خدمات: ' . $e->getMessage());
        }
    }

    /**
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function financialReport(Request $request): JsonResponse
    {
        try {
            $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
            $endDate = $request->input('end_date', now()->format('Y-m-d'));

            if (!$this->validateDates($startDate, $endDate)) {
                return $this->errorResponse('تاریخ‌های ارسالی معتبر نیستند');
            }

            $summary = $this->getFinancialSummary();
            $monthlyBreakdown = $this->getMonthlyBreakdown();
            $serviceRevenue = $this->getServiceRevenue();
            $paymentBreakdown = $this->getPaymentMethodsBreakdown();

            $trends = $this->calculateFinancialTrends($startDate, $endDate);

            Log::info('Financial report generated', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'type' => $request->input('type', 'daily')
            ]);

            $data = [
                'summary' => $summary,
                'monthly_breakdown' => $monthlyBreakdown,
                'service_revenue' => $serviceRevenue,
                'payment_breakdown' => $paymentBreakdown,
                'trends' => $trends
            ];

            return $this->successResponse($data, null, 200, [
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate,
                    'type' => $request->input('type', 'daily')
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating financial report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('خطا در تولید گزارش مالی: ' . $e->getMessage(), 500);
        }
    }

    /**
     *
     * @param string $startDate
     * @param string $endDate
     * @return bool
     */
    private function validateDates(string $startDate, string $endDate): bool
    {
        try {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);

            return $start->lte($end) && $end->lte(now());
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    private function calculateFinancialTrends(string $startDate, string $endDate): array
    {
        $currentPeriod = [Carbon::parse($startDate), Carbon::parse($endDate)];
        $interval = $currentPeriod[0]->diffInDays($currentPeriod[1]);

        $previousPeriodEnd = $currentPeriod[0]->copy()->subDay();
        $previousPeriodStart = $previousPeriodEnd->copy()->subDays($interval);

        $currentRevenue = Booking::where('payment_status', 'paid')
            ->whereBetween('created_at', [$currentPeriod[0], $currentPeriod[1]])
            ->sum('prepayment_amount');

        $previousRevenue = Booking::where('payment_status', 'paid')
            ->whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])
            ->sum('prepayment_amount');

        $currentBookings = Booking::whereBetween('created_at', [$currentPeriod[0], $currentPeriod[1]])
            ->count();

        $previousBookings = Booking::whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])
            ->count();

        $revenueChange = $previousRevenue > 0
            ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100
            : ($currentRevenue > 0 ? 100 : 0);

        $bookingsChange = $previousBookings > 0
            ? (($currentBookings - $previousBookings) / $previousBookings) * 100
            : ($currentBookings > 0 ? 100 : 0);

        return [
            'revenue_change' => round($revenueChange, 2),
            'bookings_change' => round($bookingsChange, 2),
            'previous_period' => [
                'start' => $previousPeriodStart->toDateString(),
                'end' => $previousPeriodEnd->toDateString(),
                'revenue' => $previousRevenue,
                'bookings' => $previousBookings
            ],
            'current_period' => [
                'start' => $currentPeriod[0]->toDateString(),
                'end' => $currentPeriod[1]->toDateString(),
                'revenue' => $currentRevenue,
                'bookings' => $currentBookings
            ]
        ];
    }

    /**
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function specialistPerformanceReport(Request $request): JsonResponse
    {
        try {
            $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
            $endDate = $request->input('end_date', now()->format('Y-m-d'));
            $type = $request->input('type', 'monthly');

            if (!$this->validateDates($startDate, $endDate)) {
                return $this->errorResponse('تاریخ‌های ارسالی معتبر نیستند');
            }

            $specialists = Specialist::with(['bookings' => function($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }])
                ->withCount(['bookings as total_bookings' => function($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                }])
                ->withSum(['bookings as total_revenue' => function($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate])
                        ->where('payment_status', 'paid');
                }], 'prepayment_amount')
                ->withAvg(['bookings as average_booking_value' => function($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate])
                        ->where('payment_status', 'paid');
                }], 'prepayment_amount')
                ->get()
                ->map(function($specialist) {
                    return [
                        'id' => $specialist->id,
                        'name' => $specialist->name,
                        'total_bookings' => $specialist->total_bookings ?? 0,
                        'total_revenue' => $specialist->total_revenue ?? 0,
                        'average_booking_value' => $specialist->average_booking_value ?? 0,
                        'booking_completion_rate' => $this->calculateCompletionRate($specialist->bookings),
                        'customer_return_rate' => $this->calculateReturnRate($specialist->bookings),
                        'performance_score' => $this->calculateSpecialistScore($specialist)
                    ];
                });

            Log::info('Specialist performance report generated', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'type' => $type
            ]);

            return $this->successResponse(['specialists' => $specialists], null, 200, [
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate,
                    'type' => $type
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating specialist performance report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('خطا در تولید گزارش عملکرد متخصصین: ' . $e->getMessage(), 500);
        }
    }

    /**
     *
     * @param Specialist $specialist
     * @return float
     */
    private function calculateSpecialistScore(Specialist $specialist): float
    {
        $completionRate = $this->calculateCompletionRate($specialist->bookings);
        $returnRate = $this->calculateReturnRate($specialist->bookings);
        $bookingCount = $specialist->total_bookings ?: 0;
        $revenue = $specialist->total_revenue ?: 0;

        $score = ($completionRate * 0.3) +
            ($returnRate * 0.3) +
            (min($bookingCount / 100, 1) * 20) +
            (min($revenue / 1000000, 1) * 20);

        return round($score, 2);
    }

    /**
     *
     * @param mixed $data
     * @param string|null $message
     * @param int $status
     * @param array $meta
     * @return JsonResponse
     */
    protected function successResponse(mixed $data, string $message = null, int $status = 200, array $meta = []): JsonResponse
    {
        $response = [
            'success' => true,
            'data' => $data
        ];

        if ($message) {
            $response['message'] = $message;
        }

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $status);
    }

    /**
     *
     * @param string $message
     * @param int $status
     * @param array|null $errors
     * @return JsonResponse
     */
    protected function errorResponse(string $message, int $status = 400, array $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }
}
