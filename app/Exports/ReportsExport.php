<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ReportsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $data;
    protected $type;

    public function __construct($data, $type)
    {
        $this->data = $data;
        $this->type = $type;
    }

    public function collection(): \Illuminate\Support\Collection
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        return match($this->type) {
            'daily' => ['تاریخ', 'تعداد نوبت', 'درآمد'],
            'weekly' => ['شروع هفته', 'پایان هفته', 'تعداد نوبت', 'درآمد', 'میانگین نوبت'],
            'monthly' => ['سال', 'ماه', 'تعداد نوبت', 'درآمد', 'میانگین نوبت'],
            default => array_keys((array) $this->data[0] ?? [])
        };
    }

    public function map($row): array
    {
        return match($this->type) {
            'daily' => [
                $row->date,
                $row->total_bookings,
                $row->revenue
            ],
            'weekly' => [
                $row->week_start,
                $row->week_end,
                $row->total_bookings,
                $row->revenue,
                $row->average_booking_value
            ],
            'monthly' => [
                $row->year,
                $row->month,
                $row->total_bookings,
                $row->revenue,
                $row->average_booking_value
            ],
            default => (array) $row
        };
    }
}
