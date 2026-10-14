<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\PlatformSetting;
use App\Models\Role;
use App\Models\User;
use App\Support\PlatformSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * «تنظیمات پلتفرم» سوپرادمین (۲۰۲۶-۱۰-۱۴): راه‌های تماس صفحه‌ی فروش بدون ویرایش .env؛ خالی = پیش‌فرض .env.
 */
class PlatformSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        $user = User::factory()->create(['is_admin' => true]);
        $user->assignRole(Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']));

        return $user;
    }

    public function test_saved_contacts_appear_on_the_sales_page(): void
    {
        $this->actingAs($this->superAdmin())->put(route('superadmin.settings.update'), [
            'support_phone' => '۰۲۱-۹۱۰۰ ۱۲۳۴',
            'instagram_url' => 'https://instagram.com/mahru.ir',
            'telegram_url' => 'https://t.me/mahru',
            'demo_notify_email' => 'sales@mahru.ir',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame('021-9100 1234', PlatformSettings::get('support_phone'));
        $this->assertSame('sales@mahru.ir', PlatformSettings::get('demo_notify_email'));

        auth()->logout();
        $this->get(route('central.home'))
            ->assertSee('href="tel:021-9100 1234"', false)
            ->assertSee('href="https://instagram.com/mahru.ir"', false)
            ->assertSee('href="https://t.me/mahru"', false)
            ->assertDontSee('>لینکدین<', false);
    }

    public function test_an_empty_field_falls_back_to_the_env_default(): void
    {
        config(['brand.support_phone' => '02100000000']);
        PlatformSettings::save(['support_phone' => '02111111111']);
        $this->assertSame('02111111111', PlatformSettings::get('support_phone'));

        $this->actingAs($this->superAdmin())->put(route('superadmin.settings.update'), ['support_phone' => ''])->assertRedirect();

        $this->assertNull(PlatformSetting::where('key', 'support_phone')->value('value'));
        $this->assertSame('02100000000', PlatformSettings::get('support_phone'));
    }

    public function test_invalid_values_are_rejected(): void
    {
        $this->actingAs($this->superAdmin())->put(route('superadmin.settings.update'), [
            'support_phone' => 'call me', 'public_email' => 'not-mail', 'instagram_url' => 'javascript:alert(1)',
        ])->assertSessionHasErrors(['support_phone', 'public_email', 'instagram_url']);

        $this->assertSame(0, PlatformSetting::count());
    }

    public function test_only_the_super_admin_can_open_or_change_them(): void
    {
        $salonAdmin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($salonAdmin)->get(route('superadmin.settings.edit'))->assertForbidden();
        $this->actingAs($salonAdmin)->put(route('superadmin.settings.update'), ['support_phone' => '02112345678'])->assertForbidden();
        $this->assertNull(PlatformSettings::get('support_phone'));

        $this->actingAs($this->superAdmin())->get(route('superadmin.settings.edit'))->assertOk()->assertSee('ایمیل گیرنده‌ی درخواست‌های دمو');
    }
}
