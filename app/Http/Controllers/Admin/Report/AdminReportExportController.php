<?php

namespace App\Http\Controllers\Admin\Report;

use App\Http\Controllers\Controller;
use App\Jobs\GeneratePdfReportJob;
use App\Models\ReportExport;
use App\Services\Admin\Report\AdminReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminReportExportController extends Controller
{
    public function __construct(
        protected AdminReportService $reportService,
    ) {}

    /**
     * Request report output — instead of synchronously creating the file, it just creates a pending
     * record and leaves the actual file generation to {@see GeneratePdfReportJob}
     * (see the "R-Jobs — GeneratePdfReportJob" section in the project prompt).
     */
    public function export(Request $request): RedirectResponse
    {
        $format = $this->resolveFormat($request);
        $reportType = $this->resolveReportType($request);

        ['startDate' => $startDate, 'endDate' => $endDate]
            = $this->reportService->parseDateRange($request->only('start_date', 'end_date'));

        $reportExport = ReportExport::create([
            'admin_user_id' => $request->user()->id,
            'format' => $format,
            'report_type' => $reportType,
            'filters' => ['start_date' => $startDate, 'end_date' => $endDate],
            'status' => 'pending',
        ]);

        GeneratePdfReportJob::dispatch($reportExport->id);

        return redirect()->route('admin.reports.exports.index')
            ->with('success', 'درخواست خروجی گزارش ثبت شد؛ به محض آماده شدن از همین صفحه قابل دانلود است.');
    }

    /**
     * List of all requested outputs (by each admin) — because this is an internal tool
     * * Small team of admins, intentionally not limited to "just my own requests".
     */
    public function index(): View
    {
        $exports = ReportExport::with('adminUser')
            ->latest()
            ->paginate(20);

        return view('admin.reports.exports.index', compact('exports'));
    }

    /**
     * Return type is a union, not just RedirectResponse: the "not ready" branch below returns a
     * redirect (back()), but the actual successful download path returns whatever
     * Storage::disk()->download() gives back — a StreamedResponse on the local driver. The
     * previous RedirectResponse-only type-hint made every successful PDF/Excel download a fatal
     * TypeError (confirmed via a real laravel.log the user sent), even though the file itself was
     * generated correctly and sat ready on disk.
     */
    public function download(ReportExport $reportExport): RedirectResponse|StreamedResponse
    {
        if (! $reportExport->isDownloadable()) {
            return back()->with('error', 'این فایل هنوز آماده نیست یا در دسترس نیست.');
        }

        $extension = $reportExport->format === 'excel' ? 'xlsx' : 'pdf';

        return Storage::disk('local')->download(
            $reportExport->file_path,
            "report-{$reportExport->report_type}-{$reportExport->id}.{$extension}"
        );
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
}
