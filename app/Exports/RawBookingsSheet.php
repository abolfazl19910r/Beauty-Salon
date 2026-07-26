<?php

namespace App\Exports;

use App\Exports\Concerns\AppliesReportSheetStyle;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

/**
 * R-Observers (addendum): third sheet, added after a user compared "کل نوبت‌ها: 8" on the PDF
 * against the 7 rows in the paid-only "جزئیات درآمد" table and couldn't tell where the 8th booking
 * went (it wasn't a discount/zero-payment case — it's simply that total_bookings counts every
 * status, while the revenue/payment-breakdown sheets only ever include payment_status='paid'
 * bookings). Rather than only ever showing pre-aggregated numbers, this sheet lists every real
 * booking in the period — any status — so every total above can be checked against real rows
 * instead of trusted blindly.
 */
class RawBookingsSheet implements FromCollection, WithColumnWidths, WithEvents, WithHeadings, WithMapping, WithStrictNullComparison, WithTitle
{
    use AppliesReportSheetStyle;

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
            $row['booking_date'] ?? '',
            $row['booking_time'] ?? '',
            $row['customer'] ?? '',
            $row['customer_phone'] ?? '',
            $row['service'] ?? '',
            $row['specialist'] ?? '',
            $row['status'] ?? '',
            $row['payment_status'] ?? '',
            $row['payment_method'] ?? '',
            $row['amount'] ?? 0,
            $row['specialist_share'] ?? '—',
            $row['discount_code'] ?? '',
            $row['discount_amount'] ?? 0,
            $row['payment_reference'] ?? '',
            $row['rating'] ?? '',
            $row['cancellation_reason'] ?? '',
            $row['refund_status'] ?? '',
            $row['refunded_amount'] ?? '—',
        ];
    }

    public function headings(): array
    {
        return [
            'تاریخ ثبت', 'ساعت ثبت', 'تاریخ نوبت', 'ساعت نوبت',
            'مشتری', 'شماره تماس', 'خدمت', 'متخصص',
            'وضعیت نوبت', 'وضعیت پرداخت', 'روش پرداخت',
            'مبلغ (تومان)', 'سهم متخصص (تومان)', 'کد تخفیف', 'مبلغ تخفیف (تومان)',
            'کد پیگیری پرداخت', 'امتیاز', 'دلیل لغو', 'وضعیت بازگشت وجه', 'مبلغ بازگشتی (تومان)',
        ];
    }

    public function title(): string
    {
        return 'جزئیات خام نوبت‌ها';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 13, 'B' => 10, 'C' => 13, 'D' => 10,
            'E' => 18, 'F' => 14, 'G' => 18, 'H' => 14,
            'I' => 16, 'J' => 16, 'K' => 18,
            'L' => 14, 'M' => 16, 'N' => 13, 'O' => 16,
            'P' => 16, 'Q' => 9, 'R' => 20, 'S' => 16, 'T' => 16,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = 1 + $this->bookings->count();

                $this->styleReportSheet($sheet, 20, $lastRow);

                // Status highlight map: [background hex, text hex]
                $statusColors = [
                    'در انتظار'         => ['FDE9C8', '8A5300'],
                    'در انتظار پرداخت'   => ['FCE4E4', 'A32D2D'],
                    'تایید شده'         => ['D7F0DE', '0F6E3E'],
                    'انجام شده'         => ['D7F0DE', '0F6E3E'],
                    'لغو شده'           => ['FCE4E4', 'A32D2D'],
                ];
                $paymentStatusColors = [
                    'پرداخت‌شده'  => ['D7F0DE', '0F6E3E'],
                    'پرداخت‌نشده' => ['FCE4E4', 'A32D2D'],
                ];

                foreach ($this->bookings->values() as $index => $booking) {
                    $row = $index + 2;
                    $booking = (array) $booking;

                    if (isset($statusColors[$booking['status'] ?? ''])) {
                        [$bg, $text] = $statusColors[$booking['status']];
                        $this->highlightCells($sheet, "I{$row}", $bg, $text);
                    }
                    if (isset($paymentStatusColors[$booking['payment_status'] ?? ''])) {
                        [$bg, $text] = $paymentStatusColors[$booking['payment_status']];
                        $this->highlightCells($sheet, "J{$row}", $bg, $text);
                    }
                }
            },
        ];
    }
}
