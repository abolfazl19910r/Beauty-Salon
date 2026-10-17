<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * دکمه‌ی «چشم» فیلدهای رمز (درخواست ۲۰۲۶-۱۰-۱۷): اسکریپت در همه‌ی layoutهایی که فیلد رمز دارند هست.
 */
class PasswordRevealTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_pages_include_the_reveal_script_once(): void
    {
        $slug = app(\App\Support\CurrentSalon::class)->get()->slug;
        foreach (['/login', '/salon-signup', route('salon.login', ['salon_slug' => $slug]), route('salon.register', ['salon_slug' => $slug])] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertSame(1, substr_count($html, '<script data-password-reveal>'), $url);
            $this->assertStringContainsString('type="password"', $html, $url);
        }
    }

    public function test_panel_layouts_include_the_reveal_script(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->get(route('admin.profile.edit'))->assertOk()->assertSee('data-password-reveal', false);

        $super = User::factory()->create(['is_admin' => true]);
        $super->roles()->attach(Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']));
        $super->salons()->detach();
        $this->actingAs($super)->get(route('superadmin.settings.edit'))->assertOk()->assertSee('data-password-reveal', false);
    }
}
