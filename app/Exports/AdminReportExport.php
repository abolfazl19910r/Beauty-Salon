<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * R-Observers: composes the revenue trend sheet (ReportsExport), the payment breakdown sheet
 * (PaymentBreakdownSheet), and the raw per-booking sheet (RawBookingsSheet) into one workbook, so
 * the Excel export gained these without needing a second download/route. GeneratePdfReportJob now
 * builds this instead of ReportsExport directly; the underlying sheet classes are unchanged otherwise.
 *
 * R-Observers (addendum): $specialists/$services are the same raw collections
 * AdminReportService::buildExportData() already builds for the PDF's "عملکرد متخصصین"/"خدمات
 * پرطرفدار" tables — passed through here so ReportsExport can fold the same two tables (plus a
 * revenue chart) into the existing "روند درآمد" sheet instead of adding new sheets for them.
 */
class AdminReportExport implements WithMultipleSheets
{
    public function __construct(
        protected Collection $rows,
        protected string $type,
        protected array $summary,
        protected array $paymentBreakdown,
        protected Collection $rawBookings,
        protected Collection $specialists,
        protected Collection $services,
    ) {
    }

    public function sheets(): array
    {
        return [
            new ReportsExport($this->rows, $this->type, $this->specialists, $this->services),
            new PaymentBreakdownSheet($this->summary, $this->paymentBreakdown),
            new RawBookingsSheet($this->rawBookings),
        ];
    }
}
