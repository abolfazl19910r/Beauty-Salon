<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\Salon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ فاز ۲ SaaS، محور «۲. چند ادمین برای یک سالن» — پرمیشن `manage-wallet` (اضافه‌شده در
 * 2026_09_19_000201_add_salon_staff_finance_permissions.php) باید مسیرهای کیف‌پول/صورتحساب را
 * برای یک staff بدون نقش «finance-access» مسدود کند، و برای owner (is_admin=true، طبق bypass
 * مستندشده‌ی User::hasPermission()) و برای staffِ دارای finance-access باز بگذارد.
 */
class SalonStaffFinancePermissionTest extends TestCase
{
    use RefreshDatabase;

    private Salon $salon;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->salon = Salon::where('slug', 'rasta')->firstOrFail();
        $this->owner = User::factory()->create(['is_admin' => true]);
    }

    private function makeStaff(bool $financeAccess = false): User
    {
        $staff = User::factory()->create(['is_admin' => false, 'user_type' => 'staff']);
        $this->salon->admins()->attach($staff->id, ['role' => 'staff']);

        $staffRole = Role::where('name', 'staff')->firstOrFail();
        $roleIds = [$staffRole->id];
        if ($financeAccess) {
            $roleIds[] = Role::where('name', 'finance-access')->firstOrFail()->id;
        }
        $staff->roles()->sync($roleIds);

        return $staff;
    }

    public function test_staff_without_finance_access_is_blocked_from_wallet(): void
    {
        $staff = $this->makeStaff(financeAccess: false);

        $this->actingAs($staff)->get('/admin/wallet')->assertStatus(403);
    }

    public function test_staff_without_finance_access_is_blocked_from_billing(): void
    {
        $staff = $this->makeStaff(financeAccess: false);

        $this->actingAs($staff)->get('/admin/billing')->assertStatus(403);
    }

    public function test_staff_with_finance_access_can_reach_wallet(): void
    {
        $staff = $this->makeStaff(financeAccess: true);

        $this->actingAs($staff)->get('/admin/wallet')->assertOk();
    }

    public function test_staff_can_still_reach_manual_booking_creation(): void
    {
        $staff = $this->makeStaff(financeAccess: false);

        $this->actingAs($staff)->get('/admin/bookings/create')->assertOk();
    }

    public function test_staff_without_finance_access_is_blocked_from_reports(): void
    {
        $staff = $this->makeStaff(financeAccess: false);

        $this->actingAs($staff)->get('/admin/reports')->assertStatus(403);
    }

    public function test_staff_with_finance_access_can_reach_reports(): void
    {
        // ⭐ monthlyBreakdown() از YEAR()/MONTH() (فقط MySQL) استفاده می‌کنه که روی SQLite تست
        // وجود نداره — طبق سیاست مستندشده‌ی پروژه (AdminReportsControllerTest) بازنویسی نمی‌شه،
        // فقط mock می‌شه؛ اینجا فقط می‌خوایم مطمئن بشیم permission رد می‌شه، نه خودِ گزارش.
        $this->partialMock(\App\Services\Admin\Report\AdminReportService::class, function ($mock) {
            $mock->shouldReceive('monthlyBreakdown')->andReturn(new \Illuminate\Support\Collection([]));
        });

        $staff = $this->makeStaff(financeAccess: true);

        $this->actingAs($staff)->get('/admin/reports')->assertOk();
    }

    /**
     * ⭐ کشف حین بررسی درخواست ابوالفضل درباره‌ی گیت‌کردن reports.php: خودِ داشبورد اصلی
     * (اولین صفحه‌ای که هر ادمین از جمله یک staff می‌بینه) totalRevenue/weeklyRevenue رو مستقل
     * از manage-wallet محاسبه و نمایش می‌داد — یعنی محدودیت مالی برای همین دو رقم روی داشبورد
     * اصلاً اعمال نمی‌شد. AdminDashboardService::getOverviewData() و dashboard.blade.php هر دو
     * برای این فیکس شدن.
     */
    public function test_staff_without_finance_access_does_not_see_revenue_on_dashboard(): void
    {
        $staff = $this->makeStaff(financeAccess: false);

        $response = $this->actingAs($staff)->get('/admin');

        $response->assertOk();
        $this->assertNull($response->viewData('totalRevenue'));
        $response->assertDontSee('id="total-revenue"', false);
        $response->assertDontSee('نمودار درآمد');
    }

    /**
     * ⭐ پیگیری همون کشف بالا (۲۰۲۶-۰۹-۲۰): dashboard.blade.php فیکس شده بود، ولی endpoint
     * JSON مجزای getData() (/admin/dashboard/data — بدون هیچ consumer فعلی، ولی زنده نگه
     * داشته‌شده طبق تصمیم قبلی پروژه) همون totalRevenue رو بدون هیچ گیت مالی برمی‌گردوند؛
     * یعنی یک staff بدون finance-access می‌تونست با یک درخواست مستقیم به همین JSON endpoint،
     * دور زدن محدودیت HTML رو انجام بده. حالا مثل wallet/billing/reports زیر permission
     * manage-wallet قرار گرفته.
     */
    public function test_staff_without_finance_access_is_blocked_from_dashboard_data_json(): void
    {
        $staff = $this->makeStaff(financeAccess: false);

        $this->actingAs($staff)->getJson('/admin/dashboard/data')->assertStatus(403);
    }

    public function test_staff_with_finance_access_can_reach_dashboard_data_json(): void
    {
        $staff = $this->makeStaff(financeAccess: true);

        $this->actingAs($staff)->getJson('/admin/dashboard/data')->assertOk();
    }

    public function test_owner_sees_revenue_on_dashboard(): void
    {
        $response = $this->actingAs($this->owner)->get('/admin');

        $response->assertOk();
        $this->assertNotNull($response->viewData('totalRevenue'));
        $response->assertSee('id="total-revenue"', false);
        $response->assertSee('نمودار درآمد');
    }

    public function test_staff_with_finance_access_sees_revenue_on_dashboard(): void
    {
        $staff = $this->makeStaff(financeAccess: true);

        $response = $this->actingAs($staff)->get('/admin');

        $response->assertOk();
        $this->assertNotNull($response->viewData('totalRevenue'));
        $response->assertSee('id="total-revenue"', false);
    }

    public function test_owner_bypasses_the_wallet_permission_via_is_admin(): void
    {
        $this->actingAs($this->owner)->get('/admin/wallet')->assertOk();
    }

    public function test_staff_cannot_open_the_admin_users_management_page(): void
    {
        $staff = $this->makeStaff(financeAccess: false);

        $this->actingAs($staff)->get('/admin/users')->assertStatus(403);
    }

    public function test_owner_can_open_the_admin_users_management_page(): void
    {
        $this->actingAs($this->owner)->get('/admin/users')->assertOk();
    }
}
