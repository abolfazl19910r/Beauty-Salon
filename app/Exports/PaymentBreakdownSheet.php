<?php

namespace App\Exports;

use App\Exports\Concerns\AppliesReportSheetStyle;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

/**
 * R-Observers: previously the Excel export only contained the revenue trend rows (date/bookings/
 * revenue/average) — the payment method breakdown (wallet/gateway/admin-manual) computed in
 * AdminReportService::paymentBreakdown() was never included in any export, only (partially, and
 * incorrectly before this phase's fix) surfaced via an API endpoint nothing actually called.
 * This sheet mirrors the same breakdown now shown on admin/reports/index.blade.php and in the PDF
 * export, so all three surfaces (page, PDF, Excel) stay consistent.
 *
 * WithStrictNullComparison: without this, PhpSpreadsheet's fromArray() uses loose (==) comparison
 * against null when deciding whether a cell is "empty" — and in PHP, `0 == null` is true. So any
 * category that genuinely has zero bookings (e.g. "ثبت دستی ادمین" on a day with no admin-recorded
 * payments) had its count/amount cells silently rendered blank instead of showing 0, indistinguishable
 * from missing data. This is a documented upstream behavior (see Laravel Excel's "strict null
 * comparisons" docs / PHPOffice/PhpSpreadsheet#449), not something specific to this sheet's data.
 */
class PaymentBreakdownSheet implements FromArray, WithColumnWidths, WithEvents, WithHeadings, WithStrictNullComparison, WithTitle
{
    use AppliesReportSheetStyle;

    public function __construct(protected array $summary, protected array $paymentBreakdown) {}

    public function array(): array
    {
        $pb = $this->paymentBreakdown;
        $s = $this->summary;

        if (($pb['total'] ?? 0) === 0) {
            return [['داده‌ای برای این بازه‌ی زمانی وجود ندارد', '', '', '']];
        }

        $walletAmount = $s['wallet_payments'] ?? 0;
        $gatewayAmount = $s['gateway_payments'] ?? 0;
        $adminManualAmount = $s['admin_manual_payments'] ?? 0;

        return [
            ['کیف پول', $pb['wallet'], $pb['wallet_percent'].'٪', $walletAmount],
            ['درگاه بانکی', $pb['gateway'], $pb['gateway_percent'].'٪', $gatewayAmount],
            ['ثبت دستی ادمین', $pb['admin_manual'], $pb['admin_manual_percent'].'٪', $adminManualAmount],
            ['جمع سه دسته‌ی بالا', $pb['wallet'] + $pb['gateway'] + $pb['admin_manual'], '', $walletAmount + $gatewayAmount + $adminManualAmount],
        ];
    }

    public function headings(): array
    {
        return ['روش پرداخت', 'تعداد نوبت', 'درصد', 'مبلغ (تومان)'];
    }

    public function title(): string
    {
        return 'نحوه پرداخت';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 22,
            'B' => 14,
            'C' => 12,
            'D' => 18,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $hasData = ($this->paymentBreakdown['total'] ?? 0) > 0;
                $lastRow = $hasData ? 5 : 2;
                $totalRow = $hasData ? 5 : null;

                $this->styleReportSheet($event->sheet->getDelegate(), 4, $lastRow, $totalRow);
            },
        ];
    }
}
