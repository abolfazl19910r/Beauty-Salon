<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\User;
use App\Support\CurrentSalon;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * باگ ۲۰۲۶-۱۱-۲۴: هر ردیف جدول نوبت‌های پنل مدیر دو <td> تکراری (خدمت و متخصص) داشت و همه‌ی ستون‌ها یک خانه جابه‌جا می‌شدند.
 */
class AdminBookingsTableColumnsTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_row_has_one_cell_per_header_in_every_admin_template(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $salon = app(CurrentSalon::class)->get();
        $booking = Booking::factory()->create([
            'status' => 'confirmed',
            'booking_time' => now()->addDays(3)->setTime(14, 30),
        ]);
        $booking->load(['service', 'specialist']);

        foreach (['side', 'rail', 'top', 'dense'] as $template) {
            $salon->forceFill(['admin_theme' => $template === 'side' ? null : $template])->saveQuietly();

            $html = $this->actingAs($admin)->get(route('admin.bookings.index'))->assertOk()->getContent();
            [$headers, $rows] = $this->bookingsTable($html);

            $this->assertNotEmpty($rows, "قالب {$template}: ردیفی نیست");
            foreach ($rows as $cells) {
                $this->assertCount(count($headers), $cells, "قالب {$template}: شمار خانه‌های ردیف با سرستون‌ها برابر نیست");
            }

            $cells = $rows[0];
            $column = fn (string $title) => $cells[array_search($title, $headers, true)];

            $this->assertStringContainsString($booking->service->name, $column('خدمت'), $template);
            $this->assertStringContainsString($booking->specialist->name, $column('متخصص'), $template);
            $this->assertStringContainsString(fa_date($booking->booking_time), $column('تاریخ و ساعت'), $template);
            $this->assertStringContainsString('پرداخت', $column('پرداخت'), $template);
            $this->assertStringContainsString('تأییدشده', $column('وضعیت'), $template);
            $this->assertStringNotContainsString($booking->service->name, $column('تاریخ و ساعت'), $template);
        }
    }

    /**
     * @return array{0: list<string>, 1: list<list<string>>}
     */
    private function bookingsTable(string $html): array
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        $table = $xpath->query('//table[.//th[normalize-space()="تاریخ و ساعت"]]')->item(0);
        $this->assertInstanceOf(DOMElement::class, $table, 'جدول نوبت‌ها پیدا نشد');

        $headers = [];
        foreach ($xpath->query('.//thead//th', $table) as $th) {
            $headers[] = trim(preg_replace('/\s+/u', ' ', $th->textContent));
        }

        $rows = [];
        foreach ($xpath->query('.//tbody/tr', $table) as $tr) {
            $cells = [];
            foreach ($xpath->query('./td', $tr) as $td) {
                $cells[] = trim(preg_replace('/\s+/u', ' ', $td->textContent));
            }
            $rows[] = $cells;
        }

        return [$headers, $rows];
    }
}
