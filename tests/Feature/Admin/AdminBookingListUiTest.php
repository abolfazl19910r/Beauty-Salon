<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * بسته‌ی ۲ ظاهری (۲۰۲۶-۱۱-۲۴): فهرست نوبت‌های مدیر — تب‌ها با شمار، جستجو و فیلترها، و کار هر ردیف با یک دکمه‌ی متنی
 * (تأیید / جزئیات) + منوی «⋯» (جزئیات، ویرایش یا جابه‌جایی، «لغو نوبت…» با تأیید) به‌جای آیکن‌های بی‌برچسب.
 */
class AdminBookingListUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_tabs_filters_and_row_actions(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $pending = Booking::factory()->create(['status' => 'pending', 'booking_time' => now()->addDay()->setTime(11, 0)]);
        $done = Booking::factory()->create(['status' => 'completed', 'booking_time' => now()->subDay()->setTime(11, 0)]);

        $html = $this->actingAs($admin)->get(route('admin.bookings.index', ['tab' => 'all']))->assertOk()->getContent();

        $this->assertStringContainsString('data-bookings-tabs', $html);
        $this->assertMatchesRegularExpression('/data-tab="all"\s+aria-current="page"/', $html);
        $this->assertStringContainsString('placeholder="نام یا موبایل مشتری"', $html);
        $this->assertStringContainsString('name="specialist_id"', $html);

        // نوبت منتظر تأیید: دکمه‌ی «تأیید» + منوی «⋯» با لغو تأییدخواه
        $this->assertMatchesRegularExpression('/data-booking-actions="'.$pending->id.'".*?data-primary-action="confirm"/s', $html);
        $this->assertMatchesRegularExpression('/data-booking-actions="'.$pending->id.'".*?ویرایش یا جابه‌جایی.*?data-ui-confirm="لغو نوبت .*?data-menu-cancel/s', $html);
        // نوبت انجام‌شده: فقط «جزئیات»، بی لغو
        $doneBlock = explode('data-booking-actions="'.$done->id.'"', $html)[1];
        $doneBlock = substr($doneBlock, 0, strpos($doneBlock, '</details>'));
        $this->assertStringContainsString('data-primary-action="details"', $doneBlock);
        $this->assertStringNotContainsString('data-menu-cancel', $doneBlock);

        // آیکن‌های بی‌برچسب قدیمی و فیلتر تاریخ تقویمی حذف شدند
        $this->assertStringNotContainsString('id="date-picker"', $html);
        $this->assertStringNotContainsString('title="مشاهده"', $html);

        // لغو از منو همان مسیر قبلی است
        $this->actingAs($admin)->put(route('admin.bookings.update', $pending), ['status' => 'cancelled'])->assertRedirect();
        $this->assertSame('cancelled', $pending->fresh()->status);
    }

    public function test_empty_tab_offers_all_bookings(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Booking::factory()->create(['booking_time' => now()->addDays(20)]);

        $this->actingAs($admin)->get(route('admin.bookings.index'))->assertOk()
            ->assertSee('در این بازه نوبتی نیست.')->assertSee('دیدن همه‌ی نوبت‌ها');
    }
}
