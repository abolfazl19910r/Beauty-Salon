<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Role;
use App\Models\Salon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ رگرسیون موارد ۳ و ۴ از باگ‌های گزارش‌شده پنل سوپر ادمین (ابوالفضل، ۲۰۲۶-۰۹-۱۸، رفع‌شده
 * همان‌روز در نشست جداگانه). هر دو باگ در همان متد SuperAdminController::dashboard() بودند.
 *
 * هر تست سالن پیش‌فرض 'rasta' (سالم، اشتراک ۱۲ماهه، ساخته‌شده توسط migration
 * backfill_default_salon_and_salon_id) را هم در نظر می‌گیرد، چون همیشه در دیتابیس تست حاضر است.
 */
class DashboardStatsTest extends TestCase
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

    /**
     * باگ ۳: 'active_salons' قبلاً فقط is_suspended را چک می‌کرد، نه اعتبار اشتراک را — یک
     * سالن منقضی‌شده ولی تعلیق‌نشده هم «فعال» شمرده می‌شد.
     */
    public function test_active_salons_excludes_expired_but_unsuspended_salon(): void
    {
        $this->actingAsSuperAdmin();

        Salon::factory()->create(['is_suspended' => false, 'subscription_ends_at' => now()->subDays(5)]); // expired, not suspended
        Salon::factory()->create(['is_suspended' => true, 'subscription_ends_at' => now()->addDays(30)]);  // suspended, still valid
        Salon::factory()->create(['is_suspended' => false, 'subscription_ends_at' => now()->addDays(3)]);  // expiring soon
        Salon::factory()->create(['is_suspended' => false, 'subscription_ends_at' => now()->addDays(90)]); // healthy

        $response = $this->get('/superadmin/dashboard');

        // healthy + expiring-soon + the default 'rasta' salon = 3 (expired and suspended salons excluded)
        $this->assertSame(3, $response->viewData('stats')['active_salons']);
    }

    /**
     * باگ ۴: 'expiring_soon' از diffInDays(now()) روی یک تاریخ آینده استفاده می‌کرد که در این
     * نسخه‌ی Carbon عدد منفی برمی‌گرداند — شرط <= 7 روی هر سالنِ غیرمنقضی همیشه true بود.
     */
    public function test_expiring_soon_only_counts_salons_within_seven_days(): void
    {
        $this->actingAsSuperAdmin();

        Salon::factory()->create(['is_suspended' => false, 'subscription_ends_at' => now()->subDays(5)]);  // expired
        Salon::factory()->create(['is_suspended' => false, 'subscription_ends_at' => now()->addDays(3)]);  // truly expiring soon
        Salon::factory()->create(['is_suspended' => false, 'subscription_ends_at' => now()->addDays(61)]); // healthy, far from expiry
        Salon::factory()->create(['is_suspended' => true, 'subscription_ends_at' => now()->addDays(4)]);   // suspended (must not count)

        $response = $this->get('/superadmin/dashboard');

        // Only the salon with 3 days left should count; the default 'rasta' salon has a
        // 12-month subscription, so it's nowhere near expiry and stays out of this count.
        $this->assertSame(1, $response->viewData('stats')['expiring_soon']);
    }
}
