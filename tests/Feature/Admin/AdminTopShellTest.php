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
 * بسته‌ی ۲ب — پوسته‌ی «منوی بالایی»: کار روزانه مستقیم در ردیف دوم، ۴ گروه کشویی با کارت دوستونه
 * (آیکون، نام، توضیح یک‌خطی، میانبر)، «اشتراک تا …» در ردیف اول و جستجوی Ctrl K.
 */
class AdminTopShellTest extends TestCase
{
    use RefreshDatabase;

    private function topHtml(string $route = 'admin.dashboard'): string
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $salon = Salon::where('slug', 'rasta')->first();
        app(DefaultPlans::class)->sync();
        // پلن «clinic» همه‌ی پوسته‌های پنل را باز می‌کند (مثل SalonTemplatesTest)
        $salon->forceFill([
            'plan_id' => Plan::byKey('clinic')->id, 'subscription_type' => '1m', 'subscription_ends_at' => now()->addMonth(),
            'trial_ends_at' => null, 'is_suspended' => false, 'admin_theme' => 'top',
        ])->save();
        SalonAppearance::flush();

        return $this->actingAs($owner)->get(route($route))->assertOk()->getContent();
    }

    public function test_header_has_subscription_pill_and_search(): void
    {
        $html = $this->topHtml();

        $this->assertStringContainsString('data-sub-pill', $html);
        $this->assertStringContainsString('اشتراک تا', $html);
        $this->assertMatchesRegularExpression('/class="adm-cmd hidden md:flex"[^>]*data-ui-click="toggleSearchModal"/', $html);
    }

    public function test_daily_items_are_direct_and_groups_are_two_column_cards_with_hints(): void
    {
        $html = $this->topHtml();
        $owner = User::query()->latest('id')->first();
        $groups = collect(AdminMenu::groups($owner, Salon::where('slug', 'rasta')->first()))->where('placement', '!=', 'account');

        foreach ($groups as $group) {
            if ($group['collapsible']) {
                $this->assertStringContainsString('data-adm-top-group="'.$group['key'].'"', $html);
            }
            foreach ($group['items'] as $item) {
                $this->assertStringContainsString('data-menu-key="'.$item['key'].'"', $html, $item['key']);
                $this->assertStringContainsString('data-shortcut="'.$item['shortcut'].'"', $html, $item['key']);
                $this->assertNotEmpty($item['hint'], $item['key']);
                if ($group['collapsible']) {
                    $this->assertStringContainsString('<span class="adm-top__hint">'.e($item['hint']).'</span>', $html, $item['key']);
                }
            }
        }
        $this->assertStringContainsString('adm-top__cards', $html);
        $this->assertStringNotContainsString('onclick', $html);
    }

    public function test_every_menu_item_has_a_hint(): void
    {
        $this->assertSame([], array_values(array_diff(array_keys(AdminMenu::SHORTCUTS), array_keys(AdminMenu::HINTS))));
    }
}
