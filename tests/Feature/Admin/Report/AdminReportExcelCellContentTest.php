<?php

namespace Tests\Feature\Admin\Report;

use App\Exports\AdminReportExport;
use App\Models\Booking;
use App\Models\User;
use App\Services\Admin\Report\AdminReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * Session 5 (comprehensive test-writing phase): actual cell-content validation for the Excel
 * export, as opposed to earlier sessions' coverage which only asserted "the file exists and is
 * non-empty". This reads the real generated .xlsx with PhpSpreadsheet, exactly like a human
 * opening the file would, and checks the values in specific cells across all three sheets.
 */
class AdminReportExcelCellContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_revenue_trend_sheet_has_correct_headings_and_row_values(): void
    {
        $today = now()->startOfDay();
        Booking::factory()->count(3)->create([
            'payment_status' => 'paid',
            'prepayment_amount' => 100000,
            'paid_at' => $today,
            'created_at' => $today,
        ]);

        $service = app(AdminReportService::class);
        $rows = $service->dailyRevenue($today->copy()->startOfDay(), $today->copy()->endOfDay());
        $exportData = $service->buildExportData($today->copy()->startOfDay(), $today->copy()->endOfDay(), 'daily');

        $export = new AdminReportExport(
            $rows,
            'daily',
            $exportData['summary'],
            $exportData['paymentBreakdown'],
            $exportData['rawBookings'],
            $exportData['specialists'],
            $exportData['services'],
        );

        $path = 'test-exports/cell-content.xlsx';
        Excel::store($export, $path, 'local');

        $fullPath = storage_path('app/private/'.$path);
        if (! file_exists($fullPath)) {
            // fallback for local disk root without 'private' segment depending on filesystem config
            $fullPath = storage_path('app/'.$path);
        }
        $this->assertFileExists($fullPath);

        $spreadsheet = IOFactory::load($fullPath);

        $trendSheet = $spreadsheet->getSheetByName('روند درآمد');
        $this->assertNotNull($trendSheet);

        // headings row
        $this->assertSame('تاریخ', $trendSheet->getCell('A1')->getValue());
        $this->assertSame('تعداد نوبت', $trendSheet->getCell('B1')->getValue());
        $this->assertSame('درآمد', $trendSheet->getCell('C1')->getValue());
        $this->assertSame('میانگین نوبت', $trendSheet->getCell('D1')->getValue());

        // first data row — 3 bookings * 100,000 = 300,000 revenue, average 100,000
        $this->assertSame(3, (int) $trendSheet->getCell('B2')->getValue());
        $this->assertSame(300000, (int) $trendSheet->getCell('C2')->getValue());
        $this->assertSame(100000, (int) $trendSheet->getCell('D2')->getValue());

        @unlink($fullPath);
    }

    public function test_payment_breakdown_sheet_shows_zero_not_blank_for_empty_categories(): void
    {
        $today = now()->startOfDay();
        // Only wallet payments — gateway/admin-manual categories should show real 0s, not blanks
        // (this is exactly the WithStrictNullComparison regression documented in the sheet's own
        // docblock: 0 == null in PHP, so without that interface these cells render empty).
        $booking = Booking::factory()->create([
            'payment_status' => 'paid',
            'prepayment_amount' => 80000,
            'paid_at' => $today,
            'created_at' => $today,
        ]);
        $booking->update(['payment_details' => ['method' => 'wallet']]);

        $service = app(AdminReportService::class);
        $rows = $service->dailyRevenue($today->copy()->startOfDay(), $today->copy()->endOfDay());
        $exportData = $service->buildExportData($today->copy()->startOfDay(), $today->copy()->endOfDay(), 'daily');

        $export = new AdminReportExport(
            $rows,
            'daily',
            $exportData['summary'],
            $exportData['paymentBreakdown'],
            $exportData['rawBookings'],
            $exportData['specialists'],
            $exportData['services'],
        );

        $path = 'test-exports/cell-content-breakdown.xlsx';
        Excel::store($export, $path, 'local');
        $fullPath = storage_path('app/private/'.$path);
        if (! file_exists($fullPath)) {
            $fullPath = storage_path('app/'.$path);
        }
        $this->assertFileExists($fullPath);

        $spreadsheet = IOFactory::load($fullPath);
        $sheet = $spreadsheet->getSheetByName('نحوه پرداخت');
        $this->assertNotNull($sheet);

        $this->assertSame('روش پرداخت', $sheet->getCell('A1')->getValue());
        $this->assertSame('کیف پول', $sheet->getCell('A2')->getValue());
        $this->assertSame(1, (int) $sheet->getCell('B2')->getValue());

        // gateway (row 3) and admin-manual (row 4) categories have zero bookings —
        // must be actual 0, not an empty/null cell.
        $this->assertSame('درگاه بانکی', $sheet->getCell('A3')->getValue());
        $gatewayCount = $sheet->getCell('B3')->getValue();
        $this->assertNotNull($gatewayCount, 'gateway count cell must not be blank/null');
        $this->assertSame(0, (int) $gatewayCount);

        $this->assertSame('ثبت دستی ادمین', $sheet->getCell('A4')->getValue());
        $adminManualCount = $sheet->getCell('B4')->getValue();
        $this->assertNotNull($adminManualCount, 'admin-manual count cell must not be blank/null');
        $this->assertSame(0, (int) $adminManualCount);

        @unlink($fullPath);
    }

    public function test_raw_bookings_sheet_lists_every_booking_regardless_of_status(): void
    {
        $today = now()->startOfDay();
        Booking::factory()->create([
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'created_at' => $today,
        ]);
        Booking::factory()->create([
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'prepayment_amount' => 90000,
            'paid_at' => $today,
            'created_at' => $today,
        ]);

        $service = app(AdminReportService::class);
        $rows = $service->dailyRevenue($today->copy()->startOfDay(), $today->copy()->endOfDay());
        $exportData = $service->buildExportData($today->copy()->startOfDay(), $today->copy()->endOfDay(), 'daily');

        // both bookings (pending+unpaid, confirmed+paid) must appear here even though only
        // one of them is "paid" and would show up in the revenue-trend/payment-breakdown sheets.
        $this->assertCount(2, $exportData['rawBookings']);

        $export = new AdminReportExport(
            $rows,
            'daily',
            $exportData['summary'],
            $exportData['paymentBreakdown'],
            $exportData['rawBookings'],
            $exportData['specialists'],
            $exportData['services'],
        );

        $path = 'test-exports/cell-content-raw.xlsx';
        Excel::store($export, $path, 'local');
        $fullPath = storage_path('app/private/'.$path);
        if (! file_exists($fullPath)) {
            $fullPath = storage_path('app/'.$path);
        }
        $this->assertFileExists($fullPath);

        $spreadsheet = IOFactory::load($fullPath);
        $sheet = $spreadsheet->getSheetByName('جزئیات خام نوبت‌ها');
        $this->assertNotNull($sheet);

        // header + 2 data rows
        $this->assertSame(3, $sheet->getHighestRow());

        @unlink($fullPath);
    }

    public function test_admin_report_export_via_job_produces_a_workbook_with_all_three_sheets(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $today = now()->startOfDay();
        Booking::factory()->create([
            'payment_status' => 'paid',
            'prepayment_amount' => 50000,
            'paid_at' => $today,
            'created_at' => $today,
        ]);

        \Illuminate\Support\Facades\Storage::fake('local');

        $export = \App\Models\ReportExport::factory()->for($admin, 'adminUser')->create([
            'format' => 'excel',
            'report_type' => 'daily',
            'filters' => ['start_date' => $today->format('Y-m-d'), 'end_date' => $today->format('Y-m-d')],
            'status' => 'pending',
        ]);

        (new \App\Jobs\GeneratePdfReportJob($export->id))->handle(app(AdminReportService::class));

        $export->refresh();
        $this->assertSame('ready', $export->status);

        $fullPath = \Illuminate\Support\Facades\Storage::disk('local')->path($export->file_path);
        $spreadsheet = IOFactory::load($fullPath);

        $sheetNames = $spreadsheet->getSheetNames();
        $this->assertContains('روند درآمد', $sheetNames);
        $this->assertContains('نحوه پرداخت', $sheetNames);
        $this->assertContains('جزئیات خام نوبت‌ها', $sheetNames);
    }
}
