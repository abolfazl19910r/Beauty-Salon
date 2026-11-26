<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Services\Admin\Dashboard\SalonSetupChecklist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * بسته‌ی ۲ ظاهری (۲۰۲۶-۱۱-۲۴): داشبورد مدیر — «راه‌اندازی سالن، X از ۵»، «امروز، …» با جمله‌ی خلاصه، ۴ کارت، نوبت‌های امروز
 * با کار مستقیم، درآمد ماه شمسی، و حذف نمودار/نقش‌ها/«عملکرد ضعیف».
 */
class AdminDashboardRefreshTest extends TestCase
{
    use RefreshDatabase;

    private Salon $salon;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-24 09:00:00')); // سه‌شنبه ۳ آذر
        $this->salon = Salon::where('slug', 'rasta')->firstOrFail();
        $this->owner = User::factory()->create(['is_admin' => true]);
    }

    public function test_setup_card_counts_steps_and_staff_only_sees_the_link(): void
    {
        $this->salon->forceFill(['address' => null])->save();
        $setup = SalonSetupChecklist::for($this->salon->fresh());
        $this->assertSame(4, $setup['total']); // بسته‌ی ۷: احراز داخل قدم درگاه
        $this->assertFalse($setup['required_done']);
        $this->assertFalse(collect($setup['steps'])->firstWhere('key', 'info')['done']);
        $this->assertTrue(collect($setup['steps'])->firstWhere('key', 'gateway')['optional']);

        $html = $this->actingAs($this->owner)->get(route('admin.dashboard'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/data-salon-setup\s+open/', $html);
        $this->assertStringContainsString('data-setup-step="info" data-done="0"', $html);
        $this->assertStringContainsString('تکمیل اطلاعات سالن', $html);

        $staff = User::factory()->create(['is_admin' => false, 'user_type' => 'staff']);
        $this->salon->admins()->attach($staff->id, ['role' => 'staff']);
        $staff->roles()->sync([\App\Models\Role::where('name', 'staff')->firstOrFail()->id]);
        $html = $this->actingAs($staff)->get(route('admin.dashboard'))->assertOk()->getContent();
        $this->assertStringContainsString('لینک رزرو سالن', $html);
        $this->assertStringNotContainsString('data-setup-steps', $html);
    }

    public function test_today_summary_list_actions_and_removed_widgets(): void
    {
        $specialist = Specialist::factory()->create(['salon_id' => $this->salon->id]);
        $pending = Booking::factory()->create(['specialist_id' => $specialist->id, 'status' => 'pending', 'booking_time' => now()->setTime(12, 0)]);
        Booking::factory()->create(['specialist_id' => $specialist->id, 'status' => 'confirmed', 'booking_time' => now()->setTime(10, 30)]);
        Booking::factory()->create(['specialist_id' => $specialist->id, 'status' => 'cancelled', 'booking_time' => now()->setTime(15, 0)]);
        Booking::factory()->create(['specialist_id' => $specialist->id, 'status' => 'confirmed', 'booking_time' => now()->addDay()->setTime(10, 0)]);

        $response = $this->actingAs($this->owner)->get(route('admin.dashboard'))->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('امروز، سه‌شنبه ۳ آذر', $html);
        $this->assertStringContainsString('۲ نوبت دارید؛ ۱ نوبت منتظر تأیید یا پرداخت است.', $html);
        $this->assertSame(2, $response->viewData('todayBookingsCount'));
        // ترتیب زمانی: ۱۰:۳۰ پیش از ۱۲:۰۰
        $this->assertLessThan(strpos($html, 'data-today-booking="'.$pending->id.'"'), strpos($html, '۱۰:۳۰'));
        $this->assertMatchesRegularExpression('/data-today-booking="'.$pending->id.'".*?data-primary-action="confirm"/s', $html);

        foreach (['revenue-chart', 'نقش‌های سیستم', 'عملکرد ضعیف', 'recent-bookings-container'] as $gone) {
            $this->assertStringNotContainsString($gone, $html);
        }
        $this->assertStringContainsString('درآمد آذر', $html);
        $this->assertStringContainsString('data-dash-experts', $html);
    }

    public function test_revenue_is_the_jalali_month_and_explains_no_prepayment(): void
    {
        $specialist = Specialist::factory()->create(['salon_id' => $this->salon->id]);
        Booking::factory()->create(['specialist_id' => $specialist->id, 'status' => 'completed', 'payment_status' => 'paid',
            'prepayment_amount' => 200000, 'paid_at' => Carbon::parse('2026-11-10'), 'booking_time' => Carbon::parse('2026-11-11 10:00')]); // آبان
        $response = $this->actingAs($this->owner)->get(route('admin.dashboard'))->assertOk();
        $this->assertSame(0, $response->viewData('monthRevenue'));
        $response->assertSee('رزرو بدون پیش‌پرداخت است');

        // درآمد از دفتر کیف پول سالن خوانده می‌شود؛ پس پرداخت باید از مسیر واقعی (گذار به paid) ثبت شود
        Booking::factory()->create(['specialist_id' => $specialist->id, 'status' => 'pending', 'payment_status' => 'unpaid',
            'prepayment_amount' => 300000, 'booking_time' => Carbon::parse('2026-11-25 11:00')])
            ->update(['payment_status' => 'paid', 'paid_at' => now()]); // ۳ آذر
        $this->assertGreaterThan(0, $this->actingAs($this->owner)->get(route('admin.dashboard'))->viewData('monthRevenue'));
    }

    public function test_revenue_uses_each_experts_own_commission_and_subtracts_reversals(): void
    {
        \App\Models\WalletSetting::get()->update(['admin_commission_percentage' => 10]);
        $contract = Specialist::factory()->create(['salon_id' => $this->salon->id, 'commission_rate' => 20]); // قرارداد جدا
        $standard = Specialist::factory()->create(['salon_id' => $this->salon->id, 'commission_rate' => null]); // نرخ کلی سالن
        $slot = 0;
        $pay = function (Specialist $specialist, int $prepayment, array $extra = []) use (&$slot): Booking {
            $booking = Booking::factory()->create($extra + ['specialist_id' => $specialist->id, 'status' => 'pending',
                'payment_status' => 'unpaid', 'prepayment_amount' => $prepayment,
                'booking_time' => now()->addDays(2)->setTime(9, 0)->addHours($slot++)]);
            $booking->update(['payment_status' => 'paid', 'paid_at' => now()]);

            return $booking;
        };

        $this->travelTo(Carbon::parse('2026-11-15 10:00:00')); // آبان: در «درآمد آذر» نمی‌آید
        $pay($standard, 500000);
        $this->travelTo(Carbon::parse('2026-11-24 09:00:00'));

        $pay($contract, 200000); // ۲۰٪ ← ۴۰٬۰۰۰
        $cancelled = $pay($standard, 300000); // ۱۰٪ ← ۳۰٬۰۰۰
        $pay($standard, 100000); // ۱۰٪ ← ۱۰٬۰۰۰
        \App\Models\AdminWallet::getWallet()->deductCommission(30000, $cancelled->id); // برگشت کمیسیون نوبت لغوشده

        $response = $this->actingAs($this->owner)->get(route('admin.dashboard'))->assertOk();
        $this->assertSame(50000, $response->viewData('monthRevenue'));
        $response->assertSee('سهم سالن از پرداخت‌های آنلاین، با نرخ کمیسیون هر کارشناس');
        $response->assertDontSee('٪ کمیسیون)');

        // متن تراکنش‌ها: شماره‌ی نوبت فارسی، نام کارشناس و خدمت همان‌طور که نوشته شده
        $contract->update(['name' => 'tester2']);
        $service = \App\Models\BeautyService::factory()->create(['name' => 'Gel 3D']);
        $paid = $pay($contract->fresh(), 100000, ['service_id' => $service->id]);
        $id = \App\Support\Fa::digits((string) $paid->id);
        $this->assertSame("درآمد از نوبت #{$id} - Gel 3D",
            $contract->fresh()->wallet->transactions()->where('booking_id', $paid->id)->value('description'));
        $this->assertSame("کمیسیون از نوبت #{$id} - کارشناس: tester2",
            \App\Models\AdminWalletTransaction::where('booking_id', $paid->id)->value('description'));
    }
}
