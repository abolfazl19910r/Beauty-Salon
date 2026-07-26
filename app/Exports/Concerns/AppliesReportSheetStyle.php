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

    private function styleReportSheet(Worksheet $sheet, int $columnCount, int $lastRow, ?int $totalRow = null): void
    {
        $lastColumn = Coordinate::stringFromColumnIndex($columnCount);
        $fullRange = "A1:{$lastColumn}{$lastRow}";
        $headerRange = "A1:{$lastColumn}1";

        $sheet->setRightToLeft(true);
        $sheet->freezePane('A2');
        $sheet->getRowDimension(1)->setRowHeight(24);

        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => self::HEADER_TEXT]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::HEADER_BG]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->getStyle($fullRange)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::BORDER_COLOR]]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        for ($row = 2; $row <= $lastRow; $row++) {
            if ($totalRow && $row === $totalRow) {
                continue;
            }
            if ($row % 2 === 0) {
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

    private function highlightCells(Worksheet $sheet, string $range, string $bgHex, string $textHex): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgHex]],
            'font' => ['color' => ['rgb' => $textHex]],
        ]);
    }
}
