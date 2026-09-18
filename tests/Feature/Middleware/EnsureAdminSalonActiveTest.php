<?php

namespace Tests\Feature\Middleware;

use App\Models\Salon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ فاز ۲ از ۲، محور «۱. پرداخت آنلاین و صورتحساب» (تصمیم تأییدشده با ابوالفضل، ۲۰۲۶-۰۹-۱۹).
 * به docblock کامل EnsureAdminSalonActive نگاه کن — خلاصه: انقضای تاریخ فقط دسترسی به
 * admin.billing.* را باز نگه می‌دارد (بدون logout)، ولی suspend دستی دقیقاً مثل قبل کامل
 * logout می‌کند.
 */
class EnsureAdminSalonActiveTest extends TestCase
{
    use RefreshDatabase;

    private function adminOf(Salon $salon): User
    {
        // ⭐ عمداً is_admin=false در create() و true در update() جداگانه: اگر is_admin=true را
        // مستقیم به create() بدهیم، هوک UserFactory::afterCreating() این کاربر را قبل از اینکه
        // به سالن تست خودمان attach کنیم، خودکار به سالن پیش‌فرض تست ('rasta') هم owner می‌کند —
        // و چون user_salons()->first() آن ردیف قدیمی‌تر را برمی‌گرداند، تست عملاً سالن فعال
        // 'rasta' را چک می‌کند، نه سالن expired/suspended موردنظر خودمان. آن هوک فقط در لحظه‌ی
        // factory create() فایر می‌شود، نه در یک update() بعدی.
        $admin = User::factory()->create(['is_admin' => false, 'salon_id' => null]);
        $admin->update(['is_admin' => true]);
        $salon->admins()->attach($admin->id, ['role' => 'owner']);

        return $admin;
    }

    public function test_expired_but_not_suspended_admin_can_reach_the_billing_page(): void
    {
        $salon = Salon::factory()->expired()->create();
        $admin = $this->adminOf($salon);

        $response = $this->actingAs($admin)->get(route('admin.billing.index'));

        $response->assertOk();
        $this->assertAuthenticatedAs($admin);
    }

    public function test_expired_but_not_suspended_admin_is_redirected_away_from_other_admin_pages(): void
    {
        $salon = Salon::factory()->expired()->create();
        $admin = $this->adminOf($salon);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertRedirect(route('admin.billing.index'));
        $response->assertSessionHasErrors('subscription');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_manually_suspended_admin_is_still_fully_logged_out(): void
    {
        $salon = Salon::factory()->create(['is_suspended' => true]);
        $admin = $this->adminOf($salon);

        $response = $this->actingAs($admin)->get(route('admin.billing.index'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('phone');
        $this->assertGuest();
    }

    public function test_admin_of_an_active_salon_has_unrestricted_access(): void
    {
        $salon = Salon::factory()->create([
            'subscription_ends_at' => now()->addMonths(2),
        ]);
        $admin = $this->adminOf($salon);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
    }
}
