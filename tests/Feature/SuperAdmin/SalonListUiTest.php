<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Role;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * بسته‌ی ۱ ظاهری (۲۰۲۶-۱۱-۲۴): فهرست سالن‌ها — تراشه‌های وضعیت با شمار، «فیلترهای بیشتر»، ستون پلن و نوار مصرف کارشناس،
 * و «ویرایش» + منوی «⋯» (تمدید با تأیید، «تعلیق سالن…» با تأیید) به‌جای سه کنترل کنار هم.
 */
class SalonListUiTest extends TestCase
{
    use RefreshDatabase;

    private User $super;

    protected function setUp(): void
    {
        parent::setUp();
        app(CurrentSalon::class)->clear();
        $this->super = User::factory()->create(['is_admin' => true, 'user_type' => 'staff', 'salon_id' => null]);
        $this->super->roles()->attach(Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']));
    }

    public function test_status_chips_show_counts_and_keep_the_search(): void
    {
        Salon::factory()->create(['name' => 'یاس یک', 'subscription_ends_at' => now()->addMonths(3)]);
        Salon::factory()->create(['name' => 'یاس دو', 'subscription_ends_at' => now()->subDay()]);
        Salon::factory()->create(['name' => 'نرگس', 'subscription_ends_at' => now()->subDay()]);

        $response = $this->actingAs($this->super)->get(route('superadmin.salons.index', ['q' => 'یاس']))->assertOk();
        $counts = $response->viewData('statusCounts');
        $this->assertSame(2, $counts['total']);
        $this->assertSame(1, $counts['expired']);
        $this->assertSame(1, $counts['active']);

        $html = $response->getContent();
        $this->assertStringContainsString('data-status-chips', $html);
        $this->assertStringContainsString('data-chip="expired"', $html);
        // لینک تراشه جستجو را نگه می‌دارد
        $this->assertStringContainsString(e(route('superadmin.salons.index', ['status' => 'expired', 'q' => 'یاس'])), $html);
        // «فیلترهای بیشتر» بسته است تا فیلتری از آن انتخاب نشده
        $this->assertMatchesRegularExpression('/<details class="sa-more"\s+data-more-filters>/', $html);

        $html = $this->actingAs($this->super)->get(route('superadmin.salons.index', ['quota' => 'full']))->getContent();
        $this->assertMatchesRegularExpression('/<details class="sa-more"\s+open\s+data-more-filters>/', $html);
    }

    public function test_rows_have_plan_usage_bar_and_a_confirmed_row_menu(): void
    {
        $salon = Salon::factory()->create(['name' => 'سالن منو', 'slug' => 'menu-salon', 'max_specialists_count' => 4, 'subscription_ends_at' => now()->addMonths(2)]);
        Specialist::factory()->count(2)->create(['salon_id' => $salon->id]);
        $suspended = Salon::factory()->create(['slug' => 'paused-one', 'is_suspended' => true, 'subscription_ends_at' => now()->addMonth()]);

        $html = $this->actingAs($this->super)->get(route('superadmin.salons.index'))->assertOk()->getContent();

        $this->assertStringContainsString('data-salon-row="menu-salon"', $html);
        $this->assertStringContainsString('aria-label="۲ از ۴ کارشناس"', $html);
        $this->assertStringContainsString('بدون پلن', $html);
        $this->assertStringContainsString('aria-label="کارهای بیشتر برای سالن منو"', $html);
        foreach (['1m', '3m', '6m', '12m'] as $period) {
            $this->assertStringContainsString('data-renew="'.$period.'"', $html);
        }
        $this->assertStringContainsString('data-ui-confirm="تمدید ۳ ماه اشتراک «سالن منو»؟', $html);
        $this->assertStringContainsString('data-ui-confirm="تعلیق سالن «سالن منو»؟', $html);
        $this->assertStringContainsString('data-toggle-suspend="resume"', $html); // سالن تعلیقی: فعال‌سازی بی تأیید
        $this->assertStringNotContainsString('<select name="subscription_type"', $html); // کنترل‌های قدیمی کنار هم حذف شدند

        // راستا (سالن پیش‌فرض) تعلیق‌شدنی نیست
        $this->assertDoesNotMatchRegularExpression('/data-salon-row="rasta".*?data-toggle-suspend/s', explode('data-salon-row="menu-salon"', $html)[0]);

        // همان مسیرهای قبلی کار می‌کنند
        $this->actingAs($this->super)->post(route('superadmin.salons.renew', $salon), ['subscription_type' => '1m'])->assertRedirect();
        $this->actingAs($this->super)->post(route('superadmin.salons.toggle-suspend', $suspended))->assertRedirect();
        $this->assertFalse($suspended->fresh()->is_suspended);
    }

    public function test_the_list_does_not_query_per_row(): void
    {
        $count = function () {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->actingAs($this->super)->get(route('superadmin.salons.index'))->assertOk();

            return count(DB::getQueryLog());
        };

        Salon::factory()->count(3)->create(['trial_ends_at' => now()->addDays(5), 'subscription_ends_at' => now()->addDays(5)]);
        $count(); // گرم کردن کش‌های یک‌باره
        $few = $count();
        Salon::factory()->count(9)->create(['trial_ends_at' => now()->addDays(5), 'subscription_ends_at' => now()->addDays(5)]);
        $many = $count();

        $this->assertSame($few, $many, "queries grew from {$few} to {$many} with 9 more rows");
    }
}
