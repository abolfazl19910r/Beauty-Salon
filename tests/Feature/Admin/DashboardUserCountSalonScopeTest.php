<?php

namespace Tests\Feature\Admin;

use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ نشت بین سالن‌ها (۲۰۲۶-۰۹-۲۷، probe تفاضلی روی همه‌ی آمارها): شمارنده‌ی «کاربران» داشبورد مدیریت (صفحه و JSON
 * آمار) همه‌ی کاربرهای پلتفرم رو می‌شمرد — با اضافه‌شدن یک سالن دیگه عدد داشبورد این سالن تغییر می‌کرد. بقیه‌ی آمارها،
 * گزارش‌ها و خروجی‌های Excel/PDF در همون probe تغییری نکردند.
 */
class DashboardUserCountSalonScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_user_counters_count_only_this_salons_users(): void
    {
        $salonA = app(CurrentSalon::class)->get();
        $owner = User::factory()->create(['is_admin' => true, 'user_type' => 'staff', 'salon_id' => null]);
        User::factory()->count(2)->create(['user_type' => 'customer', 'salon_id' => $salonA->id]);
        Specialist::factory()->create();
        $expected = 4; // owner + 2 customers + specialist's user

        app(CurrentSalon::class)->set(Salon::factory()->create(['slug' => 'other-dashboard']));
        User::factory()->count(5)->create(['user_type' => 'customer']);
        User::factory()->create(['is_admin' => true, 'user_type' => 'staff', 'salon_id' => null]);
        Specialist::factory()->create();
        app(CurrentSalon::class)->set($salonA);

        $this->assertSame($expected, $this->actingAs($owner)->get(route('admin.dashboard'))->assertOk()->viewData('usersCount'));
        $this->assertSame($expected, $this->actingAs($owner)->getJson(route('admin.dashboard.data'))->assertOk()->json('stats.totalUsers'));
    }
}
