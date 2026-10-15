<?php

namespace Tests\Feature\Reports;

use App\Models\Booking;
use App\Models\Specialist;
use App\Models\User;
use App\Support\Pdf\ReportPdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ظاهر تازه‌ی گزارش‌های PDF (۲۰۲۶-۱۰-۱۵): هر دو گزارش با ReportPdf ساخته می‌شوند و فونت واقعاً Vazirmatn است
 * (قبلاً گزارش متخصص فونت ثبت‌نشده‌ی vazir را می‌خواست و autoLangToFont متن فارسی را به XB Riyaz می‌برد).
 */
class PdfReportDesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_pdf_factory_embeds_vazirmatn_and_not_the_mpdf_fallback(): void
    {
        $mpdf = ReportPdf::make();
        $mpdf->WriteHTML('<p>گزارش ماهانه‌ی سالن <b>پررنگ</b></p>');
        $pdf = $mpdf->Output('', 'S');

        $this->assertStringContainsString('Vazirmatn-Regular', $pdf);
        $this->assertStringContainsString('Vazirmatn-Bold', $pdf);
        $this->assertStringNotContainsString('XBRiyaz', $pdf);
    }

    public function test_specialist_pdf_uses_the_new_layout(): void
    {
        $specialist = Specialist::factory()->create();
        Booking::factory()->create(['specialist_id' => $specialist->id, 'status' => 'completed', 'booking_time' => now()->subDay()]);

        $html = view('specialist.reports.pdf', [
            'bookings' => Booking::with(['user', 'service'])->where('specialist_id', $specialist->id)->get(),
            'totalRevenue' => 100000, 'totalBookings' => 1, 'completedBookings' => 1, 'cancelledBookings' => 0,
            'specialist' => $specialist, 'startDate' => '1405/07/01', 'endDate' => '1405/07/30', 'commissionRate' => 10,
        ])->render();

        $this->assertStringContainsString('class="masthead"', $html);
        $this->assertStringContainsString('pill pill-good', $html);
        $this->assertStringContainsString('<sethtmlpagefooter', $html);
        $this->assertStringNotContainsString('vazir-bold', $html, 'خانواده‌ی فونت تعریف‌نشده');

        $response = $this->actingAs(User::find($specialist->user_id))->get(route('specialist.reports.index', ['export' => 'pdf']));
        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringContainsString('Vazirmatn', $response->getContent());
    }
}
