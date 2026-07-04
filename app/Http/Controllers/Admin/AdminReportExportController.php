<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportsExport;

class AdminReportExportController extends Controller
{
    public function __construct(
        protected AdminReportService $reportService,
    ) {}

    public function export(Request $request)
    {
        try {
            $format     = $this->resolveFormat($request);
            $reportType = $this->resolveReportType($request);

            ['start' => $start, 'end' => $end, 'startDate' => $startDate, 'endDate' => $endDate]
                = $this->reportService->parseDateRange($request->only('start_date', 'end_date'));

            $exportData = $this->reportService->buildExportData($start, $end, $reportType);

            return $format === 'excel'
                ? $this->exportExcel($exportData, $reportType)
                : $this->exportPdf($exportData, $reportType, $startDate, $endDate);

        } catch (\Exception $e) {
            Log::error('خطا در خروجی گزارش ادمین', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'خطا در خروجی گرفتن: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'خطا در خروجی گرفتن: ' . $e->getMessage());
        }
    }

    private function resolveFormat(Request $request): string
    {
        $format = $request->input('format');
        if (! $format && in_array($request->input('type'), ['excel', 'pdf'])) {
            $format = $request->input('type');
        }

        return $format ?: 'excel';
    }

    private function resolveReportType(Request $request): string
    {
        $reportType = $request->input('report_type');
        if (! $reportType && in_array($request->input('type'), ['daily', 'weekly', 'monthly'])) {
            $reportType = $request->input('type');
        }

        return $reportType ?: 'daily';
    }

    private function exportExcel(array $exportData, string $reportType)
    {
        return Excel::download(
            new ReportsExport($exportData['rows'], $reportType),
            "report-{$reportType}.xlsx"
        );
    }

    private function exportPdf(array $exportData, string $reportType, string $startDate, string $endDate)
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
}
