<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * R-Observers (addendum): third sheet, added after a user compared "کل نوبت‌ها: 8" on the PDF
 * against the 7 rows in the paid-only "جزئیات درآمد" table and couldn't tell where the 8th booking
 * went (it wasn't a discount/zero-payment case — it's simply that total_bookings counts every
 * status, while the revenue/payment-breakdown sheets only ever include payment_status='paid'
 * bookings). Rather than only ever showing pre-aggregated numbers, this sheet lists every real
 * booking in the period — any status — so every total above can be checked against real rows
 * instead of trusted blindly.
 */
class RawBookingsSheet implements FromCollection, WithHeadings, WithMapping, WithStrictNullComparison, WithTitle
{
    public function __construct(protected Collection $bookings)
    {
    }

    public function collection(): Collection
    {
        return $this->bookings;
    }

    public function map($row): array
    {
        $row = (array) $row;

        return [
            $row['created_date'] ?? '',
            $row['created_time'] ?? '',
            $row['customer'] ?? '',
            $row['service'] ?? '',
            $row['specialist'] ?? '',
            $row['status'] ?? '',
            $row['payment_status'] ?? '',
            $row['payment_method'] ?? '',
            $row['amount'] ?? 0,
            $row['discount_code'] ?? '',
            $row['discount_amount'] ?? 0,
        ];
    }

    public function headings(): array
    {
        return [
            'تاریخ ثبت', 'ساعت', 'مشتری', 'خدمت', 'متخصص',
            'وضعیت نوبت', 'وضعیت پرداخت', 'روش پرداخت',
            'مبلغ (تومان)', 'کد تخفیف', 'مبلغ تخفیف (تومان)',
        ];
    }

    public function title(): string
    {
        return 'جزئیات خام نوبت‌ها';
    }
}
