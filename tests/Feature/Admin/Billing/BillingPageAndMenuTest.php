<?php

namespace Tests\Feature\Admin\Billing;

use App\Models\Plan;
use App\Models\User;
use App\Services\Plans\DefaultPlans;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * پیگیری‌های ۲۰۲۶-۱۰-۱۷: «اشتراک و صورتحساب» در منوی پنل مدیر، اطلاعات کارت‌های لندینگ در صفحه‌ی خرید پلن، و هشدار QR فقط-محلی.
 */
class BillingPageAndMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_is_in_the_admin_menu_of_every_admin_template(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $salon = app(CurrentSalon::class)->get();

        foreach (['side', 'rail', 'top', 'dense'] as $template) {
            $salon->forceFill(['admin_theme' => $template === 'side' ? null : $template])->saveQuietly();
            $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()
                ->assertSee('اشتراک و صورتحساب')
                ->assertSee(route('admin.billing.index'), false);
        }
    }

    public function test_plan_cards_show_landing_details_and_a_cheaper_higher_plan(): void
    {
        app(DefaultPlans::class)->sync();
        $salon = app(CurrentSalon::class)->get();
        // ۱۰ متخصص: «پایه» (۵ متخصص شامل) با ۵ متخصص اضافه از «حرفه‌ای» (۱۰ شامل) گران‌تر است
        $salon->update(['plan_id' => Plan::byKey('basic')->id, 'max_specialists_count' => 10, 'subscription_ends_at' => now()->addDays(15)]);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get(route('admin.billing.index'))->assertOk()
            ->assertSee('data-plan-recommended', false)
            ->assertSee('data-plan-admin-themes', false)
            ->assertSee('data-period-detail="12m"', false)
            ->assertSee('قالب سایت')
            ->assertSee('٪ تخفیف')
            ->assertSee('«حرفه‌ای» با امکانات بیشتر ارزان‌تر است');

        // با ۵ متخصص قیمت‌ها همان لندینگ است و هشداری نیست
        $salon->update(['max_specialists_count' => 5]);
        $this->get(route('admin.billing.index'))->assertOk()
            ->assertSee(number_format(990000))
            ->assertDontSee('data-cheaper-plan', false);
    }

    public function test_a_local_only_qr_is_explained_and_a_network_address_is_not(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get(route('admin.billing.index'))->assertOk()
            ->assertSee('data-salon-link-local-only', false);

        $this->get('http://192.168.1.2:8000/admin/billing')->assertOk()
            ->assertSee('data-salon-qr="http://192.168.1.2:8000/s/', false)
            ->assertDontSee('data-salon-link-local-only', false);
    }
}
