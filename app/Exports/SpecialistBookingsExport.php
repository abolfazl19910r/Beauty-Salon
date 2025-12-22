<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Morilog\Jalali\Jalalian;

class SpecialistBookingsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $bookings;

    public function __construct($bookings) {
        $this->bookings = $bookings;
    }

    public function collection() {
        return $this->bookings;
    }

    public function headings(): array {
        return ['شناسه نوبت', 'نام مشتری', 'خدمت', 'تاریخ و ساعت', 'مبلغ بیعانه (تومان)', 'وضعیت'];
    }

    public function map($booking): array {
        return [
            $booking->id,
            $booking->user->name,
            $booking->service->name,
            Jalalian::fromCarbon($booking->booking_time)->format('Y/m/d H:i'),
            number_format($booking->prepayment_amount),
            $this->translateStatus($booking->status)
        ];
    }

    public function styles(Worksheet $sheet) {
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet->setRightToLeft(true);
    }

    private function translateStatus($status) {
        return match($status) {
            'completed' => 'انجام شده',
            'cancelled' => 'لغو شده',
            'pending' => 'در انتظار',
            'confirmed' => 'تایید شده',
            default => $status
        };
    }
}
