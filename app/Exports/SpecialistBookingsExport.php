<?php

namespace App\Exports;

use App\Traits\HasJalaliDates;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SpecialistBookingsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use HasJalaliDates;

    public function __construct(
        private readonly Collection $bookings,
        private readonly float $commissionRate = 10
    ) {}

    public function collection()
    {
        return $this->bookings;
    }

    public function headings(): array
    {
        return ['شناسه نوبت', 'نام مشتری', 'خدمت', 'تاریخ و ساعت', 'درآمد متخصص (تومان)', 'وضعیت'];
    }

    public function map($booking): array
    {
        $income = $booking->prepayment_amount * (1 - $this->commissionRate / 100);

        return [
            $booking->id,
            $booking->user->name,
            $booking->service->name,
            $this->toJalali($booking->booking_time, 'Y/m/d H:i'),
            number_format($income),
            $this->translateStatus($booking->status),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet->setRightToLeft(true);
    }

    private function translateStatus($status)
    {
        return match ($status) {
            'completed' => 'انجام شده',
            'cancelled' => 'لغو شده',
            'pending' => 'در انتظار',
            'confirmed' => 'تایید شده',
            default => $status
        };
    }
}
