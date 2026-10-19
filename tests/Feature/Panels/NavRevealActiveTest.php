<?php

namespace Tests\Feature\Panels;

use App\Models\Role;
use App\Models\Salon;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * بسته‌ی «ح»، ۷: منوی کناری پنل‌ها آیتم فعال را به دید می‌آورد. خود اسکرول در مرورگر (Chromium بی‌سر) سنجیده شد؛ اینجا سیم‌کشی:
 * ظرف اسکرول‌دار data-nav-scroll دارد، آیتم فعال aria-current دارد و اسکریپت مشترک بار می‌شود.
 */
class NavRevealActiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_drawer_and_rail_reveal_the_active_item(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $html = $this->actingAs($admin)->get('/admin/wallet/settings')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/<aside id="admin-drawer" data-nav-scroll/', $html);
        $this->assertMatchesRegularExpression('/data-menu-key="wallet-settings"\s+aria-current="page"/', $html);
        $this->assertStringContainsString("querySelectorAll('[data-nav-scroll]')", $html);

        // قالب «ریل» فقط در پلن‌هایی که اجازه می‌دهند (کلینیک همه را دارد)
        app(\App\Services\Plans\DefaultPlans::class)->sync();
        app(CurrentSalon::class)->get()->forceFill([
            'admin_theme' => 'rail', 'plan_id' => \App\Models\Plan::byKey('clinic')->id, 'subscription_type' => '1m',
            'subscription_ends_at' => now()->addMonth(), 'trial_ends_at' => null, 'is_suspended' => false,
        ])->save();
        \App\Support\Themes\SalonAppearance::flush();
        $html = $this->actingAs($admin)->get('/admin/wallet/settings')->assertOk()->getContent();
        $this->assertStringContainsString('<nav class="adm-rail" data-nav-scroll', $html);
    }

    public function test_super_admin_side_menu_scrolls_on_its_own_and_marks_the_active_item(): void
    {
        $role = Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']);
        $superAdmin = User::factory()->create(['is_admin' => true]);
        $superAdmin->roles()->attach($role);
        app(CurrentSalon::class)->clear();
        Salon::factory()->create();

        $html = $this->actingAs($superAdmin)->get(route('superadmin.settings.edit'))->assertOk()->getContent();

        $this->assertStringContainsString('sa-side" data-nav-scroll', $html);
        $this->assertStringContainsString('.sa-side { position: sticky; top: 0; height: 100vh; overflow-y: auto; }', $html);
        $this->assertMatchesRegularExpression('/sa-nav-link active"\s+aria-current="page"/', $html);
        $this->assertStringContainsString("querySelectorAll('[data-nav-scroll]')", $html);
    }
}
