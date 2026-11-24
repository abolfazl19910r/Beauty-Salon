<?php

namespace Tests\Feature\Admin;

use App\Models\Plan;
use App\Models\Salon;
use App\Models\User;
use App\Services\Plans\DefaultPlans;
use App\Support\Admin\AdminMenu;
use App\Support\Themes\SalonAppearance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * بسته‌ی ۲ب: هر ۴ پوسته‌ی پنل مدیر واقعاً رندر می‌شوند (پلن «clinic» همه را باز می‌کند) و در هر کدام همه‌ی آیتم‌های منو با
 * میانبرشان، نشانه‌ی ویژه‌ی همان پوسته و راهنمای میانبرها هست. پیش از این، تست‌های «هر ۴ قالب» روی پلن آزمایشی همه به
 * «منوی کناری» برمی‌گشتند.
 */
class AdminShellsMenuTest extends TestCase
{
    use RefreshDatabase;

    private const MARKERS = [
        'side' => 'data-adm-group=',
        'rail' => 'data-rail-group=',
        'top' => 'data-adm-top-group=',
        'dense' => 'adm--dense',
    ];

    public function test_every_shell_renders_with_every_menu_item_and_its_shortcut(): void
    {
        app(DefaultPlans::class)->sync();
        $salon = Salon::where('slug', 'rasta')->firstOrFail();
        $owner = User::factory()->create(['is_admin' => true]);
        $items = collect(AdminMenu::groups($owner, $salon))->flatMap(fn ($g) => $g['items']);

        foreach (self::MARKERS as $shell => $marker) {
            $salon->forceFill([
                'plan_id' => Plan::byKey('clinic')->id, 'subscription_type' => '1m', 'subscription_ends_at' => now()->addMonth(),
                'trial_ends_at' => null, 'is_suspended' => false, 'admin_theme' => $shell === 'side' ? null : $shell,
            ])->save();
            SalonAppearance::flush();

            $html = $this->actingAs($owner)->get(route('admin.dashboard'))->assertOk()->getContent();

            $this->assertStringContainsString('data-admin-shell="'.$shell.'"', $html, $shell);
            $this->assertStringContainsString($marker, $html, $shell);
            $this->assertStringContainsString('data-adm-shortcuts', $html, $shell);
            foreach ($items as $item) {
                $this->assertStringContainsString('data-menu-key="'.$item['key'].'"', $html, $shell.': '.$item['key']);
                $this->assertStringContainsString('data-shortcut="'.$item['shortcut'].'"', $html, $shell.': '.$item['key']);
            }
            $this->assertStringNotContainsString(' onclick=', $html, $shell);
        }
    }

    public function test_appearance_page_uses_the_new_shell_names(): void
    {
        app(DefaultPlans::class)->sync();
        $owner = User::factory()->create(['is_admin' => true]);

        $this->actingAs($owner)->get(route('admin.appearance.edit'))->assertOk()
            ->assertSee('منوی کناری')->assertSee('ریل آیکونی')->assertSee('منوی بالایی')->assertSee('میز پذیرش')
            ->assertDontSee('فشرده · شب‌آبی');
    }
}
