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
 * بسته‌ی ۲ب — پوسته‌ی «ریل آیکونی»: آیکون‌های کار روزانه مستقیم، ۴ دکمه‌ی گروه با aria-label/aria-expanded و
 * پنل کشویی هر گروه با همه‌ی آیتم‌ها و میانبرشان؛ «اشتراک و صورتحساب» پایین ریل.
 */
class AdminRailShellTest extends TestCase
{
    use RefreshDatabase;

    private function railHtml(string $route = 'admin.dashboard'): string
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $salon = Salon::where('slug', 'rasta')->first();
        app(DefaultPlans::class)->sync();
        // پلن «clinic» همه‌ی پوسته‌های پنل را باز می‌کند (مثل SalonTemplatesTest)
        $salon->forceFill([
            'plan_id' => Plan::byKey('clinic')->id, 'subscription_type' => '1m', 'subscription_ends_at' => now()->addMonth(),
            'trial_ends_at' => null, 'is_suspended' => false, 'admin_theme' => 'rail',
        ])->save();
        SalonAppearance::flush();

        return $this->actingAs($owner)->get(route($route))->assertOk()->getContent();
    }

    public function test_rail_has_daily_icons_and_one_button_per_group(): void
    {
        $html = $this->railHtml();

        $this->assertStringContainsString('data-adm-rail', $html);
        foreach (['salon', 'marketing', 'finance', 'settings'] as $key) {
            $this->assertMatchesRegularExpression('/data-rail-group="'.$key.'"[^>]*aria-expanded="false"[^>]*aria-controls="rail-panel-'.$key.'"/s', $html);
            $this->assertStringContainsString('data-rail-panel="'.$key.'"', $html);
        }
        $this->assertStringContainsString('aria-label="اشتراک و صورتحساب"', $html);
        $this->assertStringContainsString('با نگه‌داشتن نشانگر یا Tab باز می‌شود', $html);
        $this->assertStringNotContainsString('onclick', $html);
    }

    public function test_every_menu_item_is_reachable_in_the_rail_with_its_shortcut(): void
    {
        $html = $this->railHtml();
        $owner = User::query()->latest('id')->first();
        $items = collect(AdminMenu::groups($owner, Salon::where('slug', 'rasta')->first()))
            ->where('placement', '!=', 'account')->flatMap(fn ($g) => $g['items']);

        foreach ($items as $item) {
            $this->assertStringContainsString('data-menu-key="'.$item['key'].'"', $html, $item['key']);
            $this->assertStringContainsString('data-shortcut="'.$item['shortcut'].'"', $html, $item['key']);
        }
    }

    public function test_current_group_is_marked_and_current_item_highlighted(): void
    {
        $html = $this->railHtml('admin.billing.index');

        $this->assertMatchesRegularExpression('/adm-rail__gbtn is-current"\s+data-rail-group="finance"/', $html);
        $this->assertMatchesRegularExpression('/data-menu-key="billing"[^>]*class="adm-rail-panel__item is-active"[^>]*aria-current="page"/s', $html);
    }
}
