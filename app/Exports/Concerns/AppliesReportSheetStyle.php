<?php

namespace App\Exports\Concerns;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Shared visual style for all admin report export sheets (revenue trend, payment breakdown, raw
 * bookings). Previously these sheets used PhpSpreadsheet's bare defaults — no borders, no header
 * styling, no column widths, left-to-right layout for Persian content — making the exported files
 * hard to read compared to the PDF. This trait is applied via each sheet's AfterSheet event so the
 * three sheets stay visually consistent without duplicating the styling logic three times.
 */
trait AppliesReportSheetStyle
{
    private const HEADER_BG = '1F4E78';

    private const HEADER_TEXT = 'FFFFFF';

    private const BORDER_COLOR = 'D9E2EC';

    private const ZEBRA_BG = 'F2F7FC';

    private const TOTAL_BG = 'E7EEF5';

    /**
     * $headerRow lets this same styling be reused for sub-tables placed lower on the sheet (see
     * writeTitledSubTable() below) — RTL/freeze-pane/row-height are one-time sheet-level settings,
     * so they're only applied when styling the sheet's primary table (headerRow === 1).
     */
    private function styleReportSheet(Worksheet $sheet, int $columnCount, int $lastRow, ?int $totalRow = null, int $headerRow = 1): void
    {
        $lastColumn = Coordinate::stringFromColumnIndex($columnCount);
        $fullRange = "A{$headerRow}:{$lastColumn}{$lastRow}";
        $headerRange = "A{$headerRow}:{$lastColumn}{$headerRow}";

        if ($headerRow === 1) {
            $sheet->setRightToLeft(true);
            $sheet->freezePane('A2');
            $sheet->getRowDimension(1)->setRowHeight(24);
        }

        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => self::HEADER_TEXT]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::HEADER_BG]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->getStyle($fullRange)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::BORDER_COLOR]]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        for ($row = $headerRow + 1; $row <= $lastRow; $row++) {
            if ($totalRow && $row === $totalRow) {
                continue;
            }
            if (($row - $headerRow) % 2 === 0) {
                $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::ZEBRA_BG]],
                ]);
            }
        }

        if ($totalRow) {
            $sheet->getStyle("A{$totalRow}:{$lastColumn}{$totalRow}")->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::TOTAL_BG]],
            ]);
        }
    }

    /**
     * Writes a titled mini-table (section title row + headings + rows, or a "no data" placeholder)
     * starting at $startRow, styled consistently with the sheet's primary table. Used to fold the
     * "عملکرد متخصصین"/"خدمات پرطرفدار" blocks — previously only shown in the PDF export — into an
     * existing Excel sheet instead of adding yet another separate sheet for them.
     *
     * Returns the row number of the last row this block actually used, so the caller can compute
     * where the next block (or a chart) should start.
     */
    private function writeTitledSubTable(Worksheet $sheet, int $startRow, string $title, array $headings, array $rows): int
    {
        $columnCount = count($headings);
        $lastColumn = Coordinate::stringFromColumnIndex($columnCount);

        $sheet->setCellValue("A{$startRow}", $title);
        $sheet->mergeCells("A{$startRow}:{$lastColumn}{$startRow}");
        $sheet->getStyle("A{$startRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => self::HEADER_BG]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($startRow)->setRowHeight(22);

        $headerRow = $startRow + 1;
        $sheet->fromArray($headings, null, "A{$headerRow}");

        $firstDataRow = $headerRow + 1;

        if (count($rows) > 0) {
            $sheet->fromArray($rows, null, "A{$firstDataRow}");
            $lastRow = $firstDataRow + count($rows) - 1;
        } else {
            $sheet->setCellValue("A{$firstDataRow}", 'داده‌ای برای این بازه‌ی زمانی وجود ندارد');
            $sheet->mergeCells("A{$firstDataRow}:{$lastColumn}{$firstDataRow}");
            $lastRow = $firstDataRow;
        }

        $this->styleReportSheet($sheet, $columnCount, $lastRow, headerRow: $headerRow);

        return $lastRow;
    }

    private function highlightCells(Worksheet $sheet, string $range, string $bgHex, string $textHex): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgHex]],
            'font' => ['color' => ['rgb' => $textHex]],
        ]);
    }
}
