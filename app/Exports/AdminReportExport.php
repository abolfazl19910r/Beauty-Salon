<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * R-Observers: composes the existing single-sheet revenue export (ReportsExport) with the new
 * PaymentBreakdownSheet into one workbook, so the Excel export gained a second sheet instead of
 * needing a second download/route. GeneratePdfReportJob now builds this instead of ReportsExport
 * directly; the underlying ReportsExport/PaymentBreakdownSheet classes are unchanged otherwise.
 */
class AdminReportExport implements WithMultipleSheets
{
    public function __construct(
        protected Collection $rows,
        protected string $type,
        protected array $summary,
        protected array $paymentBreakdown,
    ) {
    }

    public function sheets(): array
    {
        return [
            new ReportsExport($this->rows, $this->type),
            new PaymentBreakdownSheet($this->summary, $this->paymentBreakdown),
        ];
    }
}
