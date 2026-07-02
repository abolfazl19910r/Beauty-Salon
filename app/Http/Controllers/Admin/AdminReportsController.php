<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminReportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportsExport;

class AdminReportsController extends Controller
{
    public function __construct(
        protected AdminReportService $reportService,
    ) {}

    public function index(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $type      = $request->input('type', 'daily');

        if (! $startDate || ! $endDate) {
            return view('admin.reports.index', [
                'startDate'        => null,
                'endDate'          => null,
                'type'             => $type,
                'summary'          => [],
                'revenueChart'     => [],
                'popularServices'  => collect(),
                'specialists'      => collect(),
                'satisfaction'     => collect(),
                'monthlyBreakdown' => collect(),
                'serviceRevenue'   => collect(),
            ]);
        }

        if (! $this->reportService->validateDates($startDate, $endDate)) {
            $startDate = now()->subDays(30)->format('Y-m-d');
            $endDate   = now()->format('Y-m-d');
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->endOfDay();

        return view('admin.reports.index', [
            'summary'          => $this->reportService->getSummary($start, $end),
            'revenueChart'     => $this->reportService->getRevenueChartData($start, $end, $type),
            'popularServices'  => $this->reportService->getPopularServices($start, $end),
            'specialists'      => $this->reportService->getSpecialistsPerformance($start, $end),
            'satisfaction'     => $this->reportService->getCustomerSatisfaction($start, $end),
            'monthlyBreakdown' => collect(),
            'serviceRevenue'   => collect(),
            'startDate'        => $startDate,
            'endDate'          => $endDate,
            'type'             => $type,
        ]);
    }

    public function exportReport(Request $request)
    {
        try {
            $format     = $request->input('format') ?: (in_array($request->input('type'), ['excel', 'pdf']) ? $request->input('type') : 'excel');
            $reportType = $request->input('report_type') ?: (in_array($request->input('type'), ['daily', 'weekly', 'monthly']) ? $request->input('type') : 'daily');
            $startDate  = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
            $endDate    = $request->input('end_date', now()->format('Y-m-d'));

            if (! $this->reportService->validateDates($startDate, $endDate)) {
                return $this->errorResponse('تاریخ‌های ارسالی معتبر نیستند');
            }

            $start      = Carbon::parse($startDate)->startOfDay();
            $end        = Carbon::parse($endDate)->endOfDay();
            $exportData = $this->reportService->buildExportData($start, $end, $reportType);

            if ($format === 'excel') {
                return Excel::download(
                    new ReportsExport($exportData['rows'], $reportType),
                    "report-{$reportType}.xlsx"
                );
            }

            return $this->exportAsPdf($exportData, $reportType, $startDate, $endDate);

        } catch (\Exception $e) {
            Log::error('خطا در خروجی گزارش ادمین', ['error' => $e->getMessage()]);

            return $this->errorResponse('خطا در خروجی گرفتن: ' . $e->getMessage(), 500);
        }
    }

    public function dailyRevenue(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate   = $request->input('end_date', now()->format('Y-m-d'));

        $data = $this->reportService->getRevenueChartData(
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay(),
            'daily'
        );

        return response()->json(['success' => true, 'data' => $data, 'meta' => [
            'period' => ['start' => $startDate, 'end' => $endDate, 'type' => 'daily'],
        ]]);
    }

    public function weeklyRevenue(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', now()->subWeeks(12)->format('Y-m-d'));
        $endDate   = $request->input('end_date', now()->format('Y-m-d'));

        if (! $this->reportService->validateDates($startDate, $endDate)) {
            return response()->json(['success' => false, 'message' => 'تاریخ‌ها معتبر نیستند'], 400);
        }

        $data = $this->reportService->getRevenueChartData(
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay(),
            'weekly'
        );

        return response()->json(['success' => true, 'data' => $data, 'meta' => [
            'period' => ['start' => $startDate, 'end' => $endDate, 'type' => 'weekly'],
        ]]);
    }

    public function monthlyRevenue(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', now()->subYear()->format('Y-m-d'));
        $endDate   = $request->input('end_date', now()->format('Y-m-d'));

        if (! $this->reportService->validateDates($startDate, $endDate)) {
            return response()->json(['success' => false, 'message' => 'تاریخ‌ها معتبر نیستند'], 400);
        }

        $data = $this->reportService->getRevenueChartData(
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay(),
            'monthly'
        );

        return response()->json(['success' => true, 'data' => $data, 'meta' => [
            'period' => ['start' => $startDate, 'end' => $endDate, 'type' => 'monthly'],
        ]]);
    }

    public function specialistPerformanceReport(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate   = $request->input('end_date', now()->format('Y-m-d'));

        if (! $this->reportService->validateDates($startDate, $endDate)) {
            return response()->json(['success' => false, 'message' => 'تاریخ‌ها معتبر نیستند'], 400);
        }

        $specialists = $this->reportService->getSpecialistsPerformance(
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay()
        );

        return response()->json(['success' => true, 'data' => ['specialists' => $specialists], 'meta' => [
            'period' => ['start' => $startDate, 'end' => $endDate],
        ]]);
    }

    public function financialReport(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate   = $request->input('end_date', now()->format('Y-m-d'));

        if (! $this->reportService->validateDates($startDate, $endDate)) {
            return response()->json(['success' => false, 'message' => 'تاریخ‌ها معتبر نیستند'], 400);
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->endOfDay();

        return response()->json(['success' => true, 'data' => [
            'summary'  => $this->reportService->getSummary($start, $end),
            'services' => $this->reportService->getPopularServices($start, $end),
            'trends'   => $this->reportService->calcFinancialTrends($startDate, $endDate),
        ], 'meta' => [
            'period' => ['start' => $startDate, 'end' => $endDate],
        ]]);
    }

    public function customerSatisfaction(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', now()->subMonths(3)->format('Y-m-d'));
        $endDate   = $request->input('end_date', now()->format('Y-m-d'));

        $satisfaction = $this->reportService->getCustomerSatisfaction(
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay()
        );

        return response()->json(['success' => true, 'data' => ['satisfaction' => $satisfaction], 'meta' => [
            'period' => ['start' => $startDate, 'end' => $endDate],
        ]]);
    }

    public function popularServices(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', now()->subMonths(3)->format('Y-m-d'));
        $endDate   = $request->input('end_date', now()->format('Y-m-d'));

        $services = $this->reportService->getPopularServices(
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay(),
            limit: 5
        )->map(fn ($s) => [
            'id'             => $s->id,
            'name'           => $s->name,
            'bookings_count' => $s->bookings_count ?? 0,
            'revenue'        => $s->bookings_sum_prepayment_amount ?? 0,
        ]);

        return response()->json(['popularServices' => $services]);
    }

    private function exportAsPdf(array $exportData, string $reportType, string $startDate, string $endDate)
    {
        $typeLabel = match ($reportType) {
            'weekly'  => 'هفتگی',
            'monthly' => 'ماهانه',
            default   => 'روزانه',
        };

        $defaultConfig     = (new \Mpdf\Config\ConfigVariables())->getDefaults();
        $defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();

        $mpdf = new \Mpdf\Mpdf([
            'mode'             => 'utf-8',
            'format'           => 'A4',
            'fontDir'          => array_merge($defaultConfig['fontDir'], [storage_path('fonts')]),
            'fontdata'         => $defaultFontConfig['fontdata'] + [
                    'vazir' => ['R' => 'Vazirmatn-Regular.ttf', 'B' => 'Vazirmatn-Bold.ttf'],
                ],
            'default_font'     => 'vazir',
            'autoScriptToLang' => true,
            'autoLangToFont'   => true,
            'margin_left'      => 12,
            'margin_right'     => 12,
            'margin_top'       => 15,
            'margin_bottom'    => 20,
            'tempDir'          => sys_get_temp_dir(),
        ]);

        $mpdf->SetDirectionality('rtl');
        $mpdf->WriteHTML(view('admin.reports.pdf-report', [
            'data'      => $exportData,
            'typeLabel' => $typeLabel,
            'period'    => ['start' => $startDate, 'end' => $endDate],
        ])->render());

        return response(
            $mpdf->Output("report-{$reportType}.pdf", \Mpdf\Output\Destination::STRING_RETURN),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"report-{$reportType}.pdf\"",
            ]
        );
    }

    private function errorResponse(string $message, int $status = 422)
    {
        if (request()->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], $status);
        }

        return back()->with('error', $message);
    }
}
