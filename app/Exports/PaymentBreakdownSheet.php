<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * R-Observers: previously the Excel export only contained the revenue trend rows (date/bookings/
 * revenue/average) — the payment method breakdown (wallet/gateway/admin-manual) computed in
 * AdminReportService::paymentBreakdown() was never included in any export, only (partially, and
 * incorrectly before this phase's fix) surfaced via an API endpoint nothing actually called.
 * This sheet mirrors the same breakdown now shown on admin/reports/index.blade.php and in the PDF
 * export, so all three surfaces (page, PDF, Excel) stay consistent.
 */
class PaymentBreakdownSheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(protected array $summary, protected array $paymentBreakdown)
    {
    }

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
            ['کیف پول', $pb['wallet'], $pb['wallet_percent'] . '٪', $walletAmount],
            ['درگاه بانکی', $pb['gateway'], $pb['gateway_percent'] . '٪', $gatewayAmount],
            ['ثبت دستی ادمین', $pb['admin_manual'], $pb['admin_manual_percent'] . '٪', $adminManualAmount],
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
}
