<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class ReportsExport implements FromCollection, WithHeadings, WithMapping, WithTitle
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
        return match ($this->type) {
            'daily'   => ['تاریخ', 'تعداد نوبت', 'درآمد', 'میانگین نوبت'],
            'weekly'  => ['هفته', 'تعداد نوبت', 'درآمد', 'میانگین نوبت'],
            'monthly' => ['ماه', 'تعداد نوبت', 'درآمد', 'میانگین نوبت'],
            default   => array_keys((array) ($this->data[0] ?? [])),
        };
    }

    /**
     * Note: The output of the AdminReportService methods (dailyRevenue/weeklyRevenue/monthlyRevenue)
     * are associative arrays with keys label, date (daily only), revenue, bookings —
     * not objects with properties date/total_bookings/week_start/... . The previous map() did not match
     * this and would have resulted in the Excel output being completely empty/zero.
     */
    public function map($row): array
    {
        $row = (array) $row;

        $label    = $row['label'] ?? ($row['date'] ?? '');
        $bookings = (int) ($row['bookings'] ?? 0);
        $revenue  = (int) ($row['revenue'] ?? 0);
        $average  = $bookings > 0 ? (int) round($revenue / $bookings) : 0;

        return [
            $label,
            $bookings,
            $revenue,
            $average,
        ];
    }

    public function title(): string
    {
        return 'روند درآمد';
    }
}
