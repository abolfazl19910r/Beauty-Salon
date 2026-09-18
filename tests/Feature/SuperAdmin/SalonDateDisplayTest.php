<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Role;
use App\Models\Salon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ رگرسیون موارد ۱ و ۲ از باگ‌های گزارش‌شده پنل سوپر ادمین (ابوالفضل، ۲۰۲۶-۰۹-۱۸، رفع‌شده
 * همان‌روز):
 * مورد ۱ - هیچ‌جا (نه index، نه dashboard) تاریخ 'از چه تاریخی فعال' (subscription_started_at)
 * نمایش داده نمی‌شد.
 * مورد ۲ - تاریخ‌ها با format('Y-m-d') خام میلادی نشان داده می‌شدند، نه با jalali_date() که
 * تقریباً همه‌ی ویوهای دیگر پروژه استفاده می‌کنند.
 */
class SalonDateDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAsSuperAdmin(): User
    {
        $superAdmin = User::factory()->create(['is_admin' => true]);
        $role = Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']);
        $superAdmin->assignRole($role);
        $this->actingAs($superAdmin);

        return $superAdmin;
    }

    public function test_salons_index_shows_jalali_dates_and_started_at(): void
    {
        $this->actingAsSuperAdmin();

        $salon = Salon::factory()->create([
            'subscription_started_at' => now()->subMonths(2),
            'subscription_ends_at' => now()->addMonths(10),
        ]);

        $response = $this->get('/superadmin/salons');

        $response->assertSee(jalali_date($salon->subscription_started_at));
        $response->assertSee(jalali_date($salon->subscription_ends_at));
        $response->assertDontSee($salon->subscription_ends_at->format('Y-m-d'));
    }

    public function test_dashboard_shows_jalali_dates_and_started_at(): void
    {
        $this->actingAsSuperAdmin();

        $salon = Salon::factory()->create([
            'subscription_started_at' => now()->subMonths(2),
            'subscription_ends_at' => now()->addMonths(10),
        ]);

        $response = $this->get('/superadmin/dashboard');

        $response->assertSee(jalali_date($salon->subscription_started_at));
        $response->assertSee(jalali_date($salon->subscription_ends_at));
        $response->assertDontSee($salon->subscription_ends_at->format('Y-m-d'));
    }
}
