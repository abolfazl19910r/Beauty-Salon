<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BeautyService;
use App\Models\Specialist;
use App\Exports\ReportsExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class AdminReportsController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $type      = $request->input('type', 'daily');

        if (!$startDate || !$endDate) {
            return view('admin.reports.index', [
                'startDate'       => null,
                'endDate'         => null,
                'type'            => $type,
                'summary'         => [],
                'revenueChart'    => [],
                'popularServices' => collect(),
                'specialists'     => collect(),
                'satisfaction'    => collect(),
                'monthlyBreakdown'=> collect(),
                'serviceRevenue'  => collect(),
            ]);
        }

        if (!$this->validateDates($startDate, $endDate)) {
            $startDate = now()->subDays(30)->format('Y-m-d');
            $endDate   = now()->format('Y-m-d');
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->endOfDay();

        $summary = [
            'total_revenue'         => Booking::where('payment_status', 'paid')
                ->whereBetween('created_at', [$start, $end])
                ->sum('prepayment_amount'),
            'total_bookings'        => Booking::whereBetween('created_at', [$start, $end])->count(),
            'completed_bookings'    => Booking::where('status', 'completed')
                ->whereBetween('created_at', [$start, $end])->count(),
            'cancelled_bookings'    => Booking::where('status', 'cancelled')
                ->whereBetween('created_at', [$start, $end])->count(),
            'pending_payments'      => Booking::where('payment_status', 'unpaid')
                ->whereBetween('created_at', [$start, $end])
                ->sum('prepayment_amount'),
            'average_booking_value' => (float)(Booking::where('payment_status', 'paid')
                ->whereBetween('created_at', [$start, $end])
                ->avg('prepayment_amount') ?? 0),
        ];

        $revenueChart = $this->getRevenueChartData($start, $end, $type);

        $popularServices = BeautyService::withCount(['bookings' => fn($q) =>
        $q->whereBetween('created_at', [$start, $end])
            ->where('status', '!=', 'cancelled')
        ])
            ->withSum(['bookings as revenue' => fn($q) =>
            $q->where('payment_status', 'paid')
                ->whereBetween('created_at', [$start, $end])
            ], 'prepayment_amount')
            ->orderByDesc('bookings_count')
            ->limit(10)
            ->get();

        $specialists = Specialist::with(['bookings' => fn($q) =>
        $q->whereBetween('created_at', [$start, $end])
        ])
            ->withCount(['bookings as total_bookings' => fn($q) =>
            $q->whereBetween('created_at', [$start, $end])
            ])
            ->withSum(['bookings as total_revenue' => fn($q) =>
            $q->whereBetween('created_at', [$start, $end])
                ->where('payment_status', 'paid')
            ], 'prepayment_amount')
            ->orderByDesc('total_bookings')
            ->get()
            ->map(fn($s) => [
                'id'                     => $s->id,
                'name'                   => $s->name,
                'total_bookings'         => $s->total_bookings  ?? 0,
                'total_revenue'          => $s->total_revenue   ?? 0,
                'booking_completion_rate'=> $this->calcCompletionRate($s->bookings),
                'customer_return_rate'   => $this->calcReturnRate($s->bookings),
            ]);

        $satisfaction = Booking::whereBetween('created_at', [$start, $end])
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
            ->map(fn($item) => [
                'specialist_name'  => $item->specialist->name ?? '—',
                'average_rating'   => $item->average_rating,
                'total_ratings'    => $item->total_ratings,
                'satisfaction_rate'=> $item->total_ratings > 0
                    ? round(($item->positive_ratings / $item->total_ratings) * 100, 1)
                    : 0,
            ]);

        $monthlyBreakdown = Booking::where('payment_status', 'paid')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(prepayment_amount) as revenue'),
                DB::raw('COUNT(*) as bookings')
            )
            ->orderBy('month')
            ->get();

        $serviceRevenue = BeautyService::withSum(['bookings as revenue' => fn($q) =>
        $q->where('payment_status', 'paid')
            ->whereBetween('created_at', [$start, $end])
        ], 'prepayment_amount')
            ->withCount(['bookings' => fn($q) =>
            $q->whereBetween('created_at', [$start, $end])
            ])
            ->orderByDesc('revenue')
            ->limit(8)
            ->get()
            ->filter(fn($s) => ($s->revenue ?? 0) > 0);

        return view('admin.reports.index', compact(
            'summary', 'revenueChart', 'popularServices',
            'specialists', 'satisfaction', 'monthlyBreakdown', 'serviceRevenue',
            'startDate', 'endDate', 'type'
        ));
    }

    public function exportReport(Request $request)
    {
        try {
            $format = $request->input('format');
            if (!$format && in_array($request->input('type'), ['excel', 'pdf'])) {
                $format = $request->input('type');
            }
            if (!$format) $format = 'excel';

            $reportType = $request->input('report_type');
            if (!$reportType && in_array($request->input('type'), ['daily', 'weekly', 'monthly'])) {
                $reportType = $request->input('type');
            }
            if (!$reportType) $reportType = 'daily';

            $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
            $endDate   = $request->input('end_date',   now()->format('Y-m-d'));

            if (!$this->validateDates($startDate, $endDate)) {
                return $this->errorResponse('تاریخ‌های ارسالی معتبر نیستند');
            }

            $start = Carbon::parse($startDate)->startOfDay();
            $end   = Carbon::parse($endDate)->endOfDay();

            $exportData = $this->buildExportData($start, $end, $reportType);

            // ── Excel ────────────────────────────────────────────
            if ($format === 'excel') {
                return Excel::download(
                    new ReportsExport($exportData['rows'], $reportType),
                    "report-{$reportType}.xlsx"
                );
            }

            // ── PDF با mPDF ──────────────────────────────────────
            if ($format === 'pdf') {
                $defaultConfig     = (new \Mpdf\Config\ConfigVariables())->getDefaults();
                $defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();

                $mpdf = new \Mpdf\Mpdf([
                    'mode'         => 'utf-8',
                    'format'       => 'A4',
                    'orientation'  => 'P',
                    'fontDir'      => array_merge($defaultConfig['fontDir'], [storage_path('fonts')]),
                    'fontdata'     => $defaultFontConfig['fontdata'] + [
                            'vazir' => [
                                'R' => 'Vazirmatn-Regular.ttf',
                                'B' => 'Vazirmatn-Bold.ttf',
                            ]
                        ],
                    'default_font' => 'vazir',
                    'margin_left'  => 12,
                    'margin_right' => 12,
                    'margin_top'   => 15,
                    'margin_bottom'=> 15,
                    'tempDir'      => sys_get_temp_dir(),
                ]);

                $mpdf->SetDirectionality('rtl');

                $typeLabel = match($reportType) {
                    'weekly'  => 'هفتگی',
                    'monthly' => 'ماهانه',
                    default   => 'روزانه',
                };

                $html = view('admin.reports.pdf-report', [
                    'data'      => $exportData,
                    'typeLabel' => $typeLabel,
                    'period'    => ['start' => $startDate, 'end' => $endDate],
                ])->render();

                $mpdf->WriteHTML($html);

                return response(
                    $mpdf->Output("report-{$reportType}.pdf", \Mpdf\Output\Destination::STRING_RETURN),
                    200,
                    [
                        'Content-Type'        => 'application/pdf',
                        'Content-Disposition' => "attachment; filename=\"report-{$reportType}.pdf\"",
                    ]
                );
            }

            return $this->errorResponse('فرمت نامعتبر');

        } catch (\Exception $e) {
            Log::error('Error exporting admin report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->errorResponse('خطا در خروجی گرفتن: ' . $e->getMessage(), 500);
        }
    }

    public function dailyRevenue(Request $request)
    {
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate   = $request->input('end_date', now()->format('Y-m-d'));

        $data = Booking::where('payment_status', 'paid')
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total_bookings'),
                DB::raw('SUM(prepayment_amount) as revenue')
            )
            ->orderBy('date')
            ->get();

        return response()->json(['success' => true, 'data' => $data, 'meta' => [
            'period' => ['start' => $startDate, 'end' => $endDate, 'type' => 'daily']
        ]]);
    }

    public function weeklyRevenue(Request $request)
    {
        $startDate = $request->input('start_date', now()->subWeeks(12)->format('Y-m-d'));
        $endDate   = $request->input('end_date', now()->format('Y-m-d'));

        if (!$this->validateDates($startDate, $endDate)) {
            return response()->json(['success' => false, 'message' => 'تاریخ‌ها معتبر نیستند'], 400);
        }

        $data = Booking::where('payment_status', 'paid')
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ])
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

        return response()->json(['success' => true, 'data' => $data, 'meta' => [
            'period' => ['start' => $startDate, 'end' => $endDate, 'type' => 'weekly']
        ]]);
    }

    public function monthlyRevenue(Request $request)
    {
        $startDate = $request->input('start_date', now()->subYear()->format('Y-m-d'));
        $endDate   = $request->input('end_date', now()->format('Y-m-d'));

        if (!$this->validateDates($startDate, $endDate)) {
            return response()->json(['success' => false, 'message' => 'تاریخ‌ها معتبر نیستند'], 400);
        }

        $data = Booking::where('payment_status', 'paid')
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ])
            ->groupBy(DB::raw('YEAR(created_at)'), DB::raw('MONTH(created_at)'))
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(*) as total_bookings'),
                DB::raw('SUM(prepayment_amount) as revenue'),
                DB::raw('AVG(prepayment_amount) as average_booking_value')
            )
            ->orderBy('year')->orderBy('month')
            ->get();

        return response()->json(['success' => true, 'data' => $data, 'meta' => [
            'period' => ['start' => $startDate, 'end' => $endDate, 'type' => 'monthly']
        ]]);
    }

    public function specialistPerformanceReport(Request $request)
    {
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate   = $request->input('end_date', now()->format('Y-m-d'));

        if (!$this->validateDates($startDate, $endDate)) {
            return response()->json(['success' => false, 'message' => 'تاریخ‌ها معتبر نیستند'], 400);
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->endOfDay();

        $specialists = Specialist::with(['bookings' => fn($q) => $q->whereBetween('created_at', [$start, $end])])
            ->withCount(['bookings as total_bookings' => fn($q) => $q->whereBetween('created_at', [$start, $end])])
            ->withSum(['bookings as total_revenue' => fn($q) =>
            $q->whereBetween('created_at', [$start, $end])->where('payment_status', 'paid')
            ], 'prepayment_amount')
            ->withAvg(['bookings as average_booking_value' => fn($q) =>
            $q->whereBetween('created_at', [$start, $end])->where('payment_status', 'paid')
            ], 'prepayment_amount')
            ->get()
            ->map(fn($s) => [
                'id'                     => $s->id,
                'name'                   => $s->name,
                'total_bookings'         => $s->total_bookings         ?? 0,
                'total_revenue'          => $s->total_revenue          ?? 0,
                'average_booking_value'  => $s->average_booking_value  ?? 0,
                'booking_completion_rate'=> $this->calcCompletionRate($s->bookings),
                'customer_return_rate'   => $this->calcReturnRate($s->bookings),
                'performance_score'      => $this->calcSpecialistScore($s),
            ]);

        return response()->json(['success' => true, 'data' => ['specialists' => $specialists], 'meta' => [
            'period' => ['start' => $startDate, 'end' => $endDate, 'type' => $request->input('type', 'daily')]
        ]]);
    }

    public function financialReport(Request $request)
    {
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate   = $request->input('end_date', now()->format('Y-m-d'));

        if (!$this->validateDates($startDate, $endDate)) {
            return response()->json(['success' => false, 'message' => 'تاریخ‌ها معتبر نیستند'], 400);
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->endOfDay();

        $summary = [
            'total_revenue'         => Booking::where('payment_status', 'paid')->whereBetween('created_at', [$start, $end])->sum('prepayment_amount'),
            'average_booking_value' => Booking::where('payment_status', 'paid')->whereBetween('created_at', [$start, $end])->avg('prepayment_amount') ?? 0,
            'pending_payments'      => Booking::where('payment_status', 'unpaid')->whereBetween('created_at', [$start, $end])->sum('prepayment_amount'),
            'refunded_amount'       => Booking::where('status', 'cancelled')->whereBetween('created_at', [$start, $end])->sum('prepayment_amount'),
        ];

        $monthlyBreakdown = Booking::where('payment_status', 'paid')
            ->where('created_at', '>=', now()->startOfYear())
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->select(DB::raw('MONTH(created_at) as month'), DB::raw('SUM(prepayment_amount) as revenue'), DB::raw('COUNT(*) as bookings'))
            ->orderBy('month')->get();

        $serviceRevenue = BeautyService::withSum('bookings', 'prepayment_amount')
            ->withCount('bookings')
            ->orderByDesc('bookings_sum_prepayment_amount')
            ->get()
            ->map(fn($s) => [
                'name'           => $s->name,
                'revenue'        => $s->bookings_sum_prepayment_amount,
                'bookings'       => $s->bookings_count,
                'average_revenue'=> $s->bookings_count > 0 ? ($s->bookings_sum_prepayment_amount / $s->bookings_count) : 0,
            ]);

        $paymentBreakdown = \App\Models\Payment::where('status', 'completed')
            ->select(DB::raw("COALESCE(gateway_reference, 'نامشخص') as payment_method"), DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total_amount'))
            ->groupBy('payment_method')->get();

        $trends = $this->calcFinancialTrends($startDate, $endDate);

        return response()->json(['success' => true, 'data' => compact('summary', 'monthlyBreakdown', 'serviceRevenue', 'paymentBreakdown', 'trends'), 'meta' => [
            'period' => ['start' => $startDate, 'end' => $endDate, 'type' => $request->input('type', 'daily')]
        ]]);
    }

    public function customerSatisfaction(Request $request)
    {
        $startDate = $request->input('start_date', now()->subMonths(3)->format('Y-m-d'));
        $endDate   = $request->input('end_date', now()->format('Y-m-d'));

        $satisfaction = Booking::whereBetween('created_at', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay()
        ])
            ->whereNotNull('rating')
            ->select('specialist_id', DB::raw('AVG(rating) as average_rating'), DB::raw('COUNT(*) as total_ratings'), DB::raw('COUNT(CASE WHEN rating >= 4 THEN 1 END) as positive_ratings'))
            ->groupBy('specialist_id')
            ->with('specialist:id,name')
            ->get()
            ->map(fn($item) => [
                'specialist_name'  => $item->specialist->name ?? '—',
                'average_rating'   => round($item->average_rating, 2),
                'total_ratings'    => $item->total_ratings,
                'satisfaction_rate'=> $item->total_ratings > 0 ? round(($item->positive_ratings / $item->total_ratings) * 100, 2) : 0,
            ]);

        return response()->json(['success' => true, 'data' => ['satisfaction' => $satisfaction], 'meta' => [
            'period' => ['start' => $startDate, 'end' => $endDate]
        ]]);
    }

    public function popularServices(Request $request)
    {
        $startDate = $request->input('start_date', now()->subMonths(3)->format('Y-m-d'));
        $endDate   = $request->input('end_date', now()->format('Y-m-d'));

        $services = BeautyService::withCount(['bookings' => fn($q) =>
        $q->whereBetween('created_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
            ->where('status', '!=', 'cancelled')
        ])
            ->withSum(['bookings' => fn($q) =>
            $q->where('payment_status', 'paid')
                ->whereBetween('created_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
            ], 'prepayment_amount')
            ->orderByDesc('bookings_count')
            ->limit(5)->get()
            ->map(fn($s) => [
                'id'             => $s->id,
                'name'           => $s->name,
                'bookings_count' => $s->bookings_count ?? 0,
                'revenue'        => $s->bookings_sum_prepayment_amount ?? 0,
            ]);

        return response()->json(['popularServices' => $services]);
    }

    private function getRevenueChartData(Carbon $start, Carbon $end, string $type): array
    {
        $jMonths = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
        $base = Booking::where('payment_status', 'paid')->whereBetween('created_at', [$start, $end]);

        if ($type === 'monthly') {
            return (clone $base)
                ->groupBy(DB::raw('YEAR(created_at)'), DB::raw('MONTH(created_at)'))
                ->select(
                    DB::raw('YEAR(created_at) as year'),
                    DB::raw('MONTH(created_at) as month'),
                    DB::raw('SUM(prepayment_amount) as revenue'),
                    DB::raw('COUNT(*) as total_bookings')
                )
                ->orderBy('year')->orderBy('month')
                ->get()
                ->map(function ($r) use ($jMonths) {
                    [$jy, $jm] = gregorian_to_jalali($r->year, $r->month, 1);
                    return [
                        'label'    => $jMonths[$jm - 1] . ' ' . $jy,
                        'revenue'  => (int)$r->revenue,
                        'bookings' => (int)$r->total_bookings,
                    ];
                })
                ->toArray();
        }

        if ($type === 'weekly') {
            return (clone $base)
                ->groupBy(DB::raw('YEARWEEK(created_at)'))
                ->select(
                    DB::raw('DATE(MIN(created_at)) as week_start'),
                    DB::raw('SUM(prepayment_amount) as revenue'),
                    DB::raw('COUNT(*) as total_bookings')
                )
                ->orderBy('week_start')
                ->get()
                ->map(function ($r) use ($jMonths) {
                    $d = Carbon::parse($r->week_start);
                    [$jy, $jm, $jd] = gregorian_to_jalali($d->year, $d->month, $d->day);
                    return [
                        'label'    => $jd . ' ' . $jMonths[$jm - 1],
                        'revenue'  => (int)$r->revenue,
                        'bookings' => (int)$r->total_bookings,
                    ];
                })
                ->toArray();
        }

        // daily
        return (clone $base)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(prepayment_amount) as revenue'),
                DB::raw('COUNT(*) as total_bookings')
            )
            ->orderBy('date')
            ->get()
            ->map(function ($r) use ($jMonths) {
                $d = Carbon::parse($r->date);
                [$jy, $jm, $jd] = gregorian_to_jalali($d->year, $d->month, $d->day);
                return [
                    'label'    => $jd . ' ' . $jMonths[$jm - 1],
                    'revenue'  => (int)$r->revenue,
                    'bookings' => (int)$r->total_bookings,
                ];
            })
            ->toArray();
    }

    private function buildExportData(Carbon $start, Carbon $end, string $type): array
    {
        $summary = [
            'total_revenue'      => Booking::where('payment_status', 'paid')->whereBetween('created_at', [$start, $end])->sum('prepayment_amount'),
            'total_bookings'     => Booking::whereBetween('created_at', [$start, $end])->count(),
            'completed_bookings' => Booking::where('status', 'completed')->whereBetween('created_at', [$start, $end])->count(),
            'cancelled_bookings' => Booking::where('status', 'cancelled')->whereBetween('created_at', [$start, $end])->count(),
        ];

        $specialists = Specialist::withCount(['bookings as total_bookings' => fn($q) =>
        $q->whereBetween('created_at', [$start, $end])
        ])
            ->withSum(['bookings as total_revenue' => fn($q) =>
            $q->whereBetween('created_at', [$start, $end])->where('payment_status', 'paid')
            ], 'prepayment_amount')
            ->orderByDesc('total_bookings')
            ->get();

        $services = BeautyService::withCount(['bookings' => fn($q) =>
        $q->whereBetween('created_at', [$start, $end])
        ])
            ->withSum(['bookings as revenue' => fn($q) =>
            $q->where('payment_status', 'paid')->whereBetween('created_at', [$start, $end])
            ], 'prepayment_amount')
            ->orderByDesc('bookings_count')
            ->limit(10)
            ->get();

        $rows = match($type) {
            'monthly' => Booking::where('payment_status', 'paid')
                ->whereBetween('created_at', [$start, $end])
                ->groupBy(DB::raw('YEAR(created_at)'), DB::raw('MONTH(created_at)'))
                ->select(
                    DB::raw('YEAR(created_at) as year'),
                    DB::raw('MONTH(created_at) as month'),
                    DB::raw('COUNT(*) as total_bookings'),
                    DB::raw('SUM(prepayment_amount) as revenue')
                )
                ->orderBy('year')->orderBy('month')->get(),

            'weekly' => Booking::where('payment_status', 'paid')
                ->whereBetween('created_at', [$start, $end])
                ->groupBy(DB::raw('YEARWEEK(created_at)'))
                ->select(
                    DB::raw('DATE(MIN(created_at)) as week_start'),
                    DB::raw('COUNT(*) as total_bookings'),
                    DB::raw('SUM(prepayment_amount) as revenue')
                )
                ->orderBy('week_start')->get(),

            default => Booking::where('payment_status', 'paid')
                ->whereBetween('created_at', [$start, $end])
                ->groupBy(DB::raw('DATE(created_at)'))
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as total_bookings'),
                    DB::raw('SUM(prepayment_amount) as revenue')
                )
                ->orderBy('date')->get(),
        };

        return compact('summary', 'specialists', 'services', 'rows');
    }

    private function calcCompletionRate($bookings): float
    {
        $total = $bookings->count();
        return $total > 0
            ? round($bookings->where('status', 'completed')->count() / $total * 100, 1)
            : 0;
    }

    private function calcReturnRate($bookings): float
    {
        $customers = $bookings->pluck('user_id')->unique();
        if ($customers->count() === 0) return 0;
        $returnCount = $bookings->groupBy('user_id')->filter(fn($b) => $b->count() > 1)->count();
        return round($returnCount / $customers->count() * 100, 1);
    }

    private function calcSpecialistScore(Specialist $specialist): float
    {
        $c = $this->calcCompletionRate($specialist->bookings);
        $r = $this->calcReturnRate($specialist->bookings);
        $b = min(($specialist->total_bookings ?? 0) / 100, 1) * 20;
        $v = min(($specialist->total_revenue  ?? 0) / 1000000, 1) * 20;
        return round($c * 0.3 + $r * 0.3 + $b + $v, 2);
    }

    private function calcFinancialTrends(string $startDate, string $endDate): array
    {
        $s       = Carbon::parse($startDate);
        $e       = Carbon::parse($endDate);
        $days    = $s->diffInDays($e);
        $prevEnd = $s->copy()->subDay();
        $prevSt  = $prevEnd->copy()->subDays($days);

        $cur  = Booking::where('payment_status','paid')->whereBetween('created_at', [$s, $e]);
        $prev = Booking::where('payment_status','paid')->whereBetween('created_at', [$prevSt, $prevEnd]);

        $curRev  = $cur->sum('prepayment_amount');
        $prevRev = $prev->sum('prepayment_amount');

        return [
            'revenue_change'  => $prevRev > 0 ? round(($curRev - $prevRev) / $prevRev * 100, 2) : ($curRev > 0 ? 100 : 0),
            'previous_period' => ['start' => $prevSt->toDateString(), 'end' => $prevEnd->toDateString(), 'revenue' => $prevRev],
            'current_period'  => ['start' => $startDate, 'end' => $endDate, 'revenue' => $curRev],
        ];
    }

    private function validateDates(string $startDate, string $endDate): bool
    {
        try {
            return Carbon::parse($startDate)->lte(Carbon::parse($endDate));
        } catch (\Exception $e) {
            return false;
        }
    }
}
