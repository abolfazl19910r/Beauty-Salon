<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * R-Observers: composes the revenue trend sheet (ReportsExport), the payment breakdown sheet
 * (PaymentBreakdownSheet), and the raw per-booking sheet (RawBookingsSheet) into one workbook, so
 * the Excel export gained these without needing a second download/route. GeneratePdfReportJob now
 * builds this instead of ReportsExport directly; the underlying sheet classes are unchanged otherwise.
 */
class AdminReportExport implements WithMultipleSheets
{
    public function __construct(
        protected Collection $rows,
        protected string $type,
        protected array $summary,
        protected array $paymentBreakdown,
        protected Collection $rawBookings,
    ) {
    }

    public function sheets(): array
    {
        return [
            new ReportsExport($this->rows, $this->type),
            new PaymentBreakdownSheet($this->summary, $this->paymentBreakdown),
            new RawBookingsSheet($this->rawBookings),
        ];
    }
}
