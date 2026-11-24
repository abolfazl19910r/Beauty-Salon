<?php

namespace Tests\Feature\Admin;

use App\Models\Salon;
use App\Models\User;
use App\Support\Admin\AdminMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * بسته‌ی ۲ب (تصمیم ابوالفضل ۱۱-۲۵): میانبرهای صفحه‌کلید برای همه‌ی آیتم‌های منو به سبک استاندارد «g/s و بعد یک حرف»،
 * راهنمای «?» با توضیح کوتاه و کلید روشن/خاموش، در هر ۴ پوسته؛ بی handler درون‌خطی.
 */
class AdminShortcutsTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_menu_item_has_a_unique_standard_shortcut(): void
    {
        $shortcuts = AdminMenu::SHORTCUTS;
        $this->assertSame(count($shortcuts), count(array_unique($shortcuts)));
        foreach ($shortcuts as $key => $sequence) {
            $this->assertMatchesRegularExpression('/^[gs] [a-z]$/', $sequence, $key);
        }

        $owner = User::factory()->create(['is_admin' => true]);
        $items = collect(AdminMenu::groups($owner, Salon::where('slug', 'rasta')->first()))->flatMap(fn ($g) => $g['items']);
        $this->assertTrue($items->every(fn ($item) => ! empty($item['shortcut'])), 'هر آیتم منو میانبر دارد');
    }

    public function test_help_dialog_and_shortcut_links_render_in_every_shell(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $salon = Salon::where('slug', 'rasta')->first();

        foreach (['side', 'rail', 'top', 'dense'] as $shell) {
            $salon->forceFill(['admin_theme' => $shell === 'side' ? null : $shell])->saveQuietly();
            $html = $this->actingAs($owner)->get(route('admin.dashboard'))->assertOk()->getContent();

            $this->assertStringContainsString('data-adm-shortcuts', $html, $shell);
            $this->assertStringContainsString('data-adm-shortcuts-toggle', $html, $shell);
            $this->assertStringContainsString('data-adm-shortcuts-open', $html, $shell);
            $this->assertStringContainsString('کلیدها را <b>پشت‌سرهم</b> بزنید', $html, $shell);
            $this->assertStringContainsString('data-shortcut="g b"', $html, $shell);
            $this->assertStringContainsString('data-shortcut="s i"', $html, $shell);
            $this->assertStringNotContainsString('onkeydown', $html, $shell);
        }
    }

    public function test_help_only_lists_pages_the_user_can_open(): void
    {
        $staff = User::factory()->create(['is_admin' => false, 'user_type' => 'staff']);
        $salon = Salon::where('slug', 'rasta')->first();
        $salon->admins()->attach($staff->id, ['role' => 'staff']);
        $staff->roles()->sync([\App\Models\Role::where('name', 'staff')->firstOrFail()->id]);

        $html = $this->actingAs($staff)->get(route('admin.dashboard'))->assertOk()->getContent();
        $this->assertStringNotContainsString('data-shortcut="s i"', $html); // اطلاعات سالن فقط مالک
        $this->assertStringNotContainsString('>اطلاعات سالن</dd>', $html);
    }
}
