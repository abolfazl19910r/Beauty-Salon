<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\DemoRequest;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\Salon;
use App\Models\SalonVerification;
use App\Models\SupportTicket;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * بسته‌ی ۱ ظاهری (۲۰۲۶-۱۱-۲۴): «نیاز به رسیدگی» با شمار و لینک، خط «موردی نیست» برای صفرها، درآمد ماه شمسی و سه کارت گروهی.
 */
class DashboardAttentionTest extends TestCase
{
    use RefreshDatabase;

    private User $super;

    protected function setUp(): void
    {
        parent::setUp();
        // ۳ آذر ۱۴۰۵؛ ماه شمسی از ۱ آذر (۲۲ نوامبر) شروع می‌شود، ماه میلادی از ۱ نوامبر
        $this->travelTo(Carbon::parse('2026-11-24 10:00:00'));
        app(CurrentSalon::class)->clear();
        $this->super = User::factory()->create(['is_admin' => true, 'user_type' => 'staff', 'salon_id' => null]);
        $this->super->roles()->attach(Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']));
    }

    public function test_attention_list_counts_and_links_and_zero_items_share_one_line(): void
    {
        $user = User::factory()->create();
        SupportTicket::factory()->count(2)->create(['user_id' => $user->id, 'status' => 'open', 'created_at' => now()->subDay()]);
        SalonVerification::query()->create(['salon_id' => Salon::factory()->create()->id, 'submitted_by' => $user->id, 'owner_name' => 'مالک',
            'owner_national_code' => '0012345678', 'license_type' => 'business', 'license_unique_id' => 'X1', 'status' => 'pending']);
        DemoRequest::factory()->count(3)->create();

        $html = $this->actingAs($this->super)->get(route('superadmin.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('data-attention="tickets"', $html);
        $this->assertStringContainsString('تیکت باز؛ قدیمی‌ترین از دیروز', $html);
        $this->assertStringContainsString('data-attention="verifications"', $html);
        $this->assertStringContainsString(route('superadmin.verifications.index'), $html);
        $this->assertStringContainsString('data-attention="demos"', $html);
        $this->assertStringContainsString('data-attention="backup"', $html); // هنوز پشتیبانی ثبت نشده
        $this->assertStringNotContainsString('data-attention="payments"', $html);
        $this->assertStringContainsString('پرداخت نیازمند بررسی و کار ناموفق در صف: موردی نیست.', $html);
        $this->assertStringContainsString('۴ مورد', $html);
    }

    public function test_revenue_uses_the_jalali_month_and_explains_zero(): void
    {
        $salon = Salon::factory()->create();
        Invoice::factory()->paid()->create(['salon_id' => $salon->id, 'amount' => 1_500_000, 'paid_at' => Carbon::parse('2026-11-10')]); // آبان
        $response = $this->actingAs($this->super)->get(route('superadmin.dashboard'))->assertOk();
        $response->assertSee('درآمد آذر')->assertSee('هنوز خرید اشتراک یا بسته‌ی پیامکی در این ماه ثبت نشده');
        $this->assertSame(0, $response->viewData('overview')['subscription_revenue_month']);

        Invoice::factory()->paid()->create(['salon_id' => $salon->id, 'amount' => 1_900_000, 'paid_at' => Carbon::parse('2026-11-23')]); // ۲ آذر
        $response = $this->actingAs($this->super)->get(route('superadmin.dashboard'))->assertOk();
        $this->assertSame(1_900_000, $response->viewData('overview')['subscription_revenue_month']);
        $response->assertSee('اشتراک <b>'.fa_num(1_900_000).'</b>', false);
    }

    public function test_salon_card_bar_and_recent_table_show_plan_and_time_left(): void
    {
        Salon::factory()->create(['name' => 'سالن نزدیک', 'subscription_ends_at' => now()->addDays(5), 'created_at' => now()->addMinute()]);

        $html = $this->actingAs($this->super)->get(route('superadmin.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('data-stat="salons"', $html);
        $this->assertStringContainsString('class="sa-bar" role="img"', $html);
        $this->assertStringContainsString('data-legend="expiring_soon"', $html);
        $this->assertStringContainsString('data-cell-plan', $html);
        $this->assertStringContainsString('۵ روز مانده', $html);
        $this->assertStringContainsString('data-cell-status="expiring_soon"', $html);
        $this->assertStringNotContainsString('data-legend="suspended"', $html); // صفر تعلیقی: پنهان
    }
}
