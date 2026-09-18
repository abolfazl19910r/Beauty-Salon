<?php

namespace Tests\Feature\Admin;

use App\Models\BeautyService;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ رگرسیون مورد ۸ از باگ‌های گزارش‌شده پنل سوپر ادمین (ابوالفضل، ۲۰۲۶-۰۹-۱۸، رفع‌شده
 * همان‌روز). تا پیش از این فیکس، هیچ‌جا سقف max_specialists_count سالن واقعاً اعمال
 * نمی‌شد — AdminSpecialistService::create() بی‌قید-و-شرط Specialist::create() صدا می‌زد.
 *
 * هر تست سالن اختصاصی خودش را می‌سازد و صریحاً CurrentSalon را روی آن ست می‌کند (به‌جای سالن
 * پیش‌فرض 'rasta' که TestCase::setUp() ست می‌کند و سقفش عمداً بزرگ است) تا سقف دقیق تحت
 * کنترل خودِ تست باشد.
 */
class AdminSpecialistQuotaTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdminForSalon(Salon $salon): User
    {
        app(CurrentSalon::class)->set($salon);

        return User::factory()->create(['is_admin' => true]);
    }

    public function test_store_is_rejected_once_salon_quota_is_reached(): void
    {
        $salon = Salon::factory()->create(['max_specialists_count' => 2]);
        $admin = $this->actingAsAdminForSalon($salon);
        Specialist::factory()->count(2)->create(); // fills the quota exactly, scoped to $salon
        $service = BeautyService::factory()->create();

        $response = $this->actingAs($admin)->post('/admin/specialists', [
            'name' => 'متخصص سوم بیش از سقف',
            'phone' => '09121234599',
            'email' => 'over-quota@example.com',
            'services' => [$service->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame(2, Specialist::count());
    }

    public function test_store_succeeds_while_under_salon_quota(): void
    {
        $salon = Salon::factory()->create(['max_specialists_count' => 2]);
        $admin = $this->actingAsAdminForSalon($salon);
        Specialist::factory()->count(1)->create(); // 1 of 2 used, scoped to $salon
        $service = BeautyService::factory()->create();

        $response = $this->actingAs($admin)->post('/admin/specialists', [
            'name' => 'متخصص دوم',
            'phone' => '09121234598',
            'email' => 'under-quota@example.com',
            'services' => [$service->id],
        ]);

        $response->assertRedirect(route('admin.specialists.index'));
        $response->assertSessionHas('success');
        $this->assertSame(2, Specialist::count());
    }

    public function test_a_zero_quota_blocks_every_new_specialist(): void
    {
        $salon = Salon::factory()->create(['max_specialists_count' => 0]);
        $admin = $this->actingAsAdminForSalon($salon);
        $service = BeautyService::factory()->create();

        $response = $this->actingAs($admin)->post('/admin/specialists', [
            'name' => 'اولین متخصص',
            'phone' => '09121234597',
            'email' => 'zero-quota@example.com',
            'services' => [$service->id],
        ]);

        $response->assertSessionHas('error');
        $this->assertSame(0, Specialist::count());
    }
}
