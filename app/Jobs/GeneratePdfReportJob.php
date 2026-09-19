<?php

namespace App\Jobs;

use App\Exports\AdminReportExport;
use App\Models\ReportExport;
use App\Notifications\Admin\Report\Export\ReportExportReadyNotification;
use App\Services\Admin\Report\AdminReportService;
use App\Support\CurrentSalon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Async generation of admin report output file (PDF or Excel).
 *
 * Previously `AdminReportExportController::export()` would create this file directly and synchronous
 * within the same request (especially mPDF which can be large for time periods
 * ) and download it directly. According to the documented decision in the R-Jobs phase, this conversion to async
 * was postponed because it required a new UX (queue + status + next download), not just
 * code relocation; this Job implements the same documented design (R-Jobs-addendum).
 *
 * A single Job for both formats (instead of two separate Jobs) because the logic of building the report data
 * (AdminReportService::buildExportData) is completely common between PDF/Excel; only the final
 * step of serializing the file differs between the two formats.
 *
 * ⭐ Fix (real، تأییدشده، کشف‌شده ۲۰۲۶-۰۹-۱۹ — پیگیری محور «۳»): این یک queued job است — هیچ
 * HTTP request/middleware chain نداره، پس CurrentSalon هیچ‌وقت به‌صورت خودکار ست نمی‌شد. چون
 * AdminReportService::buildExportData() روی Eloquent query های salon-scoped (Booking و مشابه‌ها،
 * از طریق BelongsToSalon) ساخته شده، بدون CurrentSalon هیچ فیلتری اعمال نمی‌شد — یعنی فایل
 * خروجی، دادهٔ همهٔ سالن‌ها رو با هم قاطی برمی‌گردوند. راه‌حل: migration جدید salon_id رو به
 * report_exports اضافه کرد (auto-fill شده در لحظه‌ی create() توسط BelongsToSalon، چون اون
 * لحظه هنوز داخل HTTP request واقعی هستیم)؛ اینجا، همون salon_id رو می‌خونیم و صریحاً
 * CurrentSalon رو قبل از هر query دیگه‌ای ست می‌کنیم.
 */
class GeneratePdfReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 60;

    public function __construct(
        protected int $reportExportId,
    ) {}

    public function handle(AdminReportService $reportService): void
    {
        // ⭐ این find() عمداً قبل از ست‌شدن CurrentSalon اجراست — BelongsToSalon وقتی
        // CurrentSalon ست نباشه هیچ فیلتری اضافه نمی‌کنه (رفتار مستندشده‌ی خودِ trait، دقیقاً
        // مثل پنل سوپرادمین)، پس این ردیف صرف‌نظر از اینکه به کدوم سالن تعلق داره پیدا می‌شه —
        // خودِ salon_id همین ردیف در ادامه برای ست‌کردن CurrentSalon استفاده می‌شه.
        $reportExport = ReportExport::find($this->reportExportId);

        if (! $reportExport) {
            Log::warning('GeneratePdfReportJob: رکورد ReportExport یافت نشد', [
                'report_export_id' => $this->reportExportId,
            ]);

            return;
        }

        // ⭐ بدون یک سالن مشخص، buildExportData() هیچ فیلتری اعمال نمی‌کنه و دادهٔ همهٔ
        // سالن‌ها رو مخلوط می‌کنه — این حالت (که نباید عملاً پیش بیاد، چون create() همیشه
        // salon_id رو auto-fill می‌کنه) رو صریحاً fail می‌کنیم، نه اینکه بی‌صدا ادامه بدیم.
        if (! $reportExport->salon_id) {
            $reportExport->update([
                'status' => 'failed',
                'error_message' => 'سالن این درخواست گزارش مشخص نیست.',
            ]);

            Log::error('GeneratePdfReportJob: ReportExport بدون salon_id — پردازش متوقف شد', [
                'report_export_id' => $reportExport->id,
            ]);

            return;
        }

        app(CurrentSalon::class)->set($reportExport->salon);

        // If already processed (ready/failed), don't rerun — prevents creating the file twice
        // due to retry or multiple workers running at the same time.
        if ($reportExport->status !== 'pending') {
            Log::info('GeneratePdfReportJob: وضعیت دیگر pending نیست، از پردازش صرف‌نظر شد', [
                'report_export_id' => $reportExport->id,
                'current_status' => $reportExport->status,
            ]);

            return;
        }

        $reportExport->update(['status' => 'processing']);

        try {
            $filters = $reportExport->filters ?? [];

            ['start' => $start, 'end' => $end, 'startDate' => $startDate, 'endDate' => $endDate]
                = $reportService->parseDateRange($filters);

            $exportData = $reportService->buildExportData($start, $end, $reportExport->report_type);

            $filePath = $reportExport->format === 'excel'
                ? $this->generateExcel($exportData, $reportExport)
                : $this->generatePdf($exportData, $reportExport, $startDate, $endDate);

            $reportExport->update([
                'status' => 'ready',
                'file_path' => $filePath,
                'ready_at' => now(),
            ]);

            $reportExport->adminUser?->notify(new ReportExportReadyNotification($reportExport));
        } catch (\Throwable $e) {
            $reportExport->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error('GeneratePdfReportJob: تولید فایل گزارش ناموفق بود', [
                'report_export_id' => $reportExport->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $reportExport->adminUser?->notify(new ReportExportReadyNotification($reportExport));
        }
    }

    private function generateExcel(array $exportData, ReportExport $reportExport): string
    {
        $relativePath = "report-exports/{$reportExport->id}.xlsx";

        Excel::store(
            new AdminReportExport(
                $exportData['rows'],
                $reportExport->report_type,
                $exportData['summary'],
                $exportData['paymentBreakdown'],
                $exportData['rawBookings'],
                $exportData['specialists'],
                $exportData['services'],
            ),
            $relativePath,
            'local'
        );

        return $relativePath;
    }

    private function generatePdf(array $exportData, ReportExport $reportExport, string $startDate, string $endDate): string
    {
        $typeLabel = match ($reportExport->report_type) {
            'weekly' => 'هفتگی',
            'monthly' => 'ماهانه',
            default => 'روزانه',
        };

        $defaultConfig = (new \Mpdf\Config\ConfigVariables)->getDefaults();
        $defaultFontConfig = (new \Mpdf\Config\FontVariables)->getDefaults();

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'fontDir' => array_merge($defaultConfig['fontDir'], [storage_path('fonts')]),
            'fontdata' => $defaultFontConfig['fontdata'] + [
                'vazir' => ['R' => 'Vazirmatn-Regular.ttf', 'B' => 'Vazirmatn-Bold.ttf'],
            ],
            'default_font' => 'vazir',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 15,
            'margin_bottom' => 20,
            'tempDir' => sys_get_temp_dir(),
        ]);

        $mpdf->SetDirectionality('rtl');
        $mpdf->WriteHTML(view('admin.reports.pdf-report', [
            'data' => $exportData,
            'typeLabel' => $typeLabel,
            'type' => $reportExport->report_type,
            'period' => ['start' => $startDate, 'end' => $endDate],
        ])->render());

        $relativePath = "report-exports/{$reportExport->id}.pdf";

        Storage::disk('local')->put(
            $relativePath,
            $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN)
        );

        return $relativePath;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GeneratePdfReportJob: خود Job با خطا متوقف شد', [
            'report_export_id' => $this->reportExportId,
            'error' => $exception->getMessage(),
        ]);

        $reportExport = ReportExport::find($this->reportExportId);
        if ($reportExport && in_array($reportExport->status, ['pending', 'processing'])) {
            $reportExport->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);
        }
    }
}
