<?php

namespace App\Exports;

use App\Exports\Concerns\AppliesReportSheetStyle;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;

class ReportsExport implements FromCollection, WithCharts, WithColumnWidths, WithEvents, WithHeadings, WithMapping, WithStrictNullComparison, WithTitle
{
    use AppliesReportSheetStyle;

    public function __construct(
        private readonly array $data,
        private readonly string $type,
        private readonly Collection $specialists,
        private readonly Collection $services,
    ) {
    }

    public function collection(): Collection
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

    public function columnWidths(): array
    {
        return [
            'A' => 16,
            'B' => 14,
            'C' => 18,
            'D' => 20,
        ];
    }

    /**
     * R-Observers (addendum): "عملکرد متخصصین"/"خدمات پرطرفدار" previously existed only in the PDF
     * export (see admin/reports/pdf-report.blade.php) — the Excel export had no equivalent at all.
     * These are folded into this existing sheet (below the revenue trend table), per the explicit
     * decision that they belong inside an existing sheet rather than as new standalone sheets. The
     * same "bookings > 0" filter used by the PDF is applied here for consistency between the two
     * export formats.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = 1 + $this->collection()->count();
                $this->styleReportSheet($sheet, 4, $lastRow);

                $specialistRows = $this->specialists
                    ->filter(fn ($sp) => ($sp->total_bookings ?? 0) > 0)
                    ->values()
                    ->map(fn ($sp) => [
                        $sp->name,
                        (int) ($sp->total_bookings ?? 0),
                        (int) ($sp->total_revenue ?? 0),
                    ])
                    ->all();

                $afterSpecialists = $this->writeTitledSubTable(
                    $sheet,
                    $lastRow + 2,
                    'عملکرد متخصصین',
                    ['نام متخصص', 'تعداد نوبت', 'درآمد (تومان)'],
                    $specialistRows
                );

                $serviceRows = $this->services
                    ->filter(fn ($svc) => ($svc->bookings_count ?? 0) > 0)
                    ->values()
                    ->map(fn ($svc) => [
                        $svc->name,
                        (int) ($svc->bookings_count ?? 0),
                        (int) ($svc->revenue ?? 0),
                    ])
                    ->all();

                $this->writeTitledSubTable(
                    $sheet,
                    $afterSpecialists + 2,
                    'خدمات پرطرفدار',
                    ['نام خدمت', 'تعداد نوبت', 'درآمد (تومان)'],
                    $serviceRows
                );
            },
        ];
    }

    /**
     * R-Observers (addendum): revenue-trend chart, requested alongside the two tables above. Placed
     * to the right of the tables (starting column F) so it never overlaps the vertically-stacked
     * revenue/specialist/service tables, which only use columns A-D. Uses the sheet's own title as
     * the cell-reference prefix (Laravel Excel/PhpSpreadsheet resolve chart data by sheet name, not
     * by object reference), quoted because the title contains a space.
     */
    public function charts(): array
    {
        $count = $this->collection()->count();

        if ($count === 0) {
            return [];
        }

        $lastRow = 1 + $count;
        $sheetRef = "'{$this->title()}'";

        $seriesLabel = [new DataSeriesValues('String', "{$sheetRef}!\$C\$1", null, 1)];
        $categories  = [new DataSeriesValues('String', "{$sheetRef}!\$A\$2:\$A\${$lastRow}", null, $count)];
        $values      = [new DataSeriesValues('Number', "{$sheetRef}!\$C\$2:\$C\${$lastRow}", null, $count)];

        $series = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_CLUSTERED,
            range(0, 0),
            $seriesLabel,
            $categories,
            $values
        );
        $series->setPlotDirection(DataSeries::DIRECTION_COL);

        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend(Legend::POSITION_BOTTOM, null, false);

        $chart = new Chart('revenue_trend_chart', new Title('نمودار روند درآمد'), $legend, $plotArea);
        $chart->setTopLeftPosition('F2');
        $chart->setBottomRightPosition('N22');

        return [$chart];
    }
}
