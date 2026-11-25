<?php

namespace Tests\Feature\Themes;

use App\Models\Plan;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\SuperAdminAuditLog;
use App\Models\User;
use App\Services\Api\CustomerPresenter;
use App\Services\Plans\DefaultPlans;
use App\Support\Admin\AdminMenu;
use App\Support\Themes\SalonAppearance;
use App\Support\Themes\ThemeCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ بسته‌ی «و» — قالب‌های قابل انتخاب (۲۰۲۶-۱۰-۱۵): ۱۶ قالب سایت (۴ چیدمان × ۴ پالت) + طلایی ماهرو، ۴ قالب پنل مدیر،
 * دسترسی «چند چیدمان × چند پالت» هر پلن و بازه، صفحه‌ی «ظاهر سالن»، API اپ‌ها.
 */
class SalonTemplatesTest extends TestCase
{
    use RefreshDatabase;

    private Salon $salon;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        app(DefaultPlans::class)->sync();
        $this->salon = Salon::where('slug', 'rasta')->firstOrFail();
        $this->onPlan('clinic', '1m');
        // اولین is_admin ساخته‌شده خودکار owner سالن جاری می‌شود (UserFactory::configure)
        $this->owner = User::factory()->create(['is_admin' => true]);
        SalonAppearance::flush();
    }

    private function onPlan(string $key, string $period, array $extra = []): void
    {
        $this->salon->forceFill(array_merge([
            'plan_id' => Plan::byKey($key)->id,
            'subscription_type' => $period,
            'subscription_ends_at' => now()->addMonth(),
            'trial_ends_at' => null,
            'is_suspended' => false,
        ], $extra))->save();
        SalonAppearance::flush();
    }

    private function choose(?string $site, ?string $admin = null): void
    {
        $this->salon->forceFill(['site_theme' => $site, 'admin_theme' => $admin])->save();
        SalonAppearance::flush();
    }

    // ─── فهرست قالب‌ها ───────────────────────────────────────────

    public function test_catalog_has_sixteen_site_templates_and_four_admin_templates_with_readable_palettes(): void
    {
        $this->assertCount(16, ThemeCatalog::siteKeys());
        $this->assertSame(16, ThemeCatalog::siteTotal());
        $this->assertSame(['side', 'rail', 'top', 'dense'], ThemeCatalog::adminKeys());
        $this->assertSame(3, ThemeCatalog::adminTotal());
        $this->assertSame(['rose', 'sage', 'lilac', 'turquoise'], array_keys(ThemeCatalog::palettes()));

        $luminance = function (string $channels): float {
            $c = array_map(fn ($v) => (int) $v / 255, preg_split('/\s+/', trim($channels)));
            $f = fn ($u) => $u <= 0.03928 ? $u / 12.92 : (($u + 0.055) / 1.055) ** 2.4;

            return 0.2126 * $f($c[0]) + 0.7152 * $f($c[1]) + 0.0722 * $f($c[2]);
        };
        $contrast = fn (string $a, string $b) => (max($luminance($a), $luminance($b)) + 0.05) / (min($luminance($a), $luminance($b)) + 0.05);
        $hexToChannels = fn (string $hex) => implode(' ', array_map('hexdec', str_split(ltrim($hex, '#'), 2)));

        $sets = array_map(fn ($p) => ['dark' => $p['dark'], 'light' => $p['light']], (array) config('themes.palettes'));
        foreach (['rail', 'top', 'dense'] as $admin) {
            $sets["admin:$admin"] = ThemeCatalog::adminPaletteTokens($admin);
        }
        foreach ($sets as $name => $modes) {
            foreach ($modes as $mode => $t) {
                foreach (['bg', 'bg2', 'bg3', 'card', 'tx', 'mu', 'gold', 'gold2', 'on', 'line', 'line2'] as $key) {
                    $this->assertArrayHasKey($key, $t, "$name/$mode/$key");
                }
                $this->assertGreaterThanOrEqual(4.5, $contrast($t['tx'], $t['bg']), "$name/$mode متن");
                $this->assertGreaterThanOrEqual(4.5, $contrast($t['mu'], $t['bg']), "$name/$mode متن کم‌رنگ");
                $this->assertGreaterThanOrEqual(4.5, $contrast($t['gold'], $t['bg2']), "$name/$mode رنگ اصلی روی کارت");
                $this->assertGreaterThanOrEqual(4.5, $contrast($hexToChannels($t['on']), $t['gold']), "$name/$mode متن دکمه");
            }
        }

        // بسته‌ی ۵الف: رنگ دوم هر پالت سایت (تیره و روشن)
        foreach ((array) config('themes.palettes') as $name => $palette) {
            foreach (['dark', 'light'] as $mode) {
                $t = $palette[$mode];
                $this->assertArrayHasKey('accent2', $t, "$name/$mode/accent2");
                $this->assertArrayHasKey('on2', $t, "$name/$mode/on2");
                $this->assertGreaterThanOrEqual(4.5, $contrast($t['accent2'], $t['bg']), "$name/$mode رنگ دوم روی زمینه");
                $this->assertGreaterThanOrEqual(4.5, $contrast($t['accent2'], $t['bg2']), "$name/$mode رنگ دوم روی کارت");
                $this->assertGreaterThanOrEqual(4.5, $contrast($t['gold'], $t['bg']), "$name/$mode رنگ اصلی روی زمینه");
                $this->assertGreaterThanOrEqual(4.5, $contrast($t['mu'], $t['bg3']), "$name/$mode متن کم‌رنگ روی سطح رنگی");
                $this->assertGreaterThanOrEqual(4.5, $contrast($hexToChannels($t['on2']), $t['accent2']), "$name/$mode متن روی رنگ دوم");
            }
            $this->assertSame('#FFFFFF', $palette['light']['on'], "$name: سفید روی رنگ اصلی روشن");
            $this->assertSame('#FFFFFF', $palette['light']['on2'], "$name: سفید روی رنگ دوم روشن");
        }
    }

    public function test_palettes_carry_the_approved_canvas_colours(): void
    {
        $hex = fn (string $palette, string $mode, string $key) => ThemeCatalog::rgbToHex(config("themes.palettes.$palette.$mode.$key"));

        $approved = [
            'rose' => ['#9C3F55', '#5C3A33', '#EBA5AC', '#D9B8A8'],
            'sage' => ['#335E45', '#7E5F28', '#A9CFAB', '#D8BE85'],
            'lilac' => ['#56419E', '#8A5A7A', '#C5B3F6', '#E2B4CF'],
            'turquoise' => ['#0D6F69', '#A4532F', '#5CCFC4', '#E59A72'],
        ];
        foreach ($approved as $palette => [$light, $light2, $dark, $dark2]) {
            $this->assertSame([$light, $light2, $dark, $dark2], [
                $hex($palette, 'light', 'gold'), $hex($palette, 'light', 'accent2'),
                $hex($palette, 'dark', 'gold'), $hex($palette, 'dark', 'accent2'),
            ], $palette);
        }
        $this->assertSame('#56685C', $hex('sage', 'light', 'mu'));
        $this->assertSame('#56625F', $hex('turquoise', 'light', 'mu'));
    }

    public function test_site_palette_tokens_write_accent2_and_the_default_keeps_gold2(): void
    {
        $this->choose('signature:turquoise');
        $html = $this->get(route('home'))->assertOk()->getContent();
        $this->assertStringContainsString('--m-accent2:164 83 47;', $html, 'رنگ دوم روشن فیروزه');
        $this->assertStringContainsString('--m-accent2:229 154 114;', $html, 'رنگ دوم تیره فیروزه');

        $this->choose(null);
        $html = $this->get(route('home'))->assertOk()->getContent();
        $this->assertStringContainsString('--m-accent2:227 201 133;', $html, 'طلایی ماهرو: رنگ دوم = gold2');
        $this->assertStringNotContainsString('data-theme-palette', $html);
    }

    public function test_allowed_templates_follow_the_super_admin_order(): void
    {
        $this->assertSame(['signature:rose', 'signature:sage', 'editorial:rose', 'editorial:sage'], ThemeCatalog::allowedSiteKeys(2, 2));

        ThemeCatalog::saveOrder(['app', 'showcase', 'editorial', 'signature'], ['turquoise', 'lilac', 'sage', 'rose']);
        $this->assertSame(['app:turquoise', 'showcase:turquoise'], ThemeCatalog::allowedSiteKeys(2, 1));
        $this->assertSame(['side', 'rail'], ThemeCatalog::allowedAdminKeys(1));

        $this->expectException(\InvalidArgumentException::class);
        ThemeCatalog::saveOrder(['app', 'app', 'editorial', 'signature'], ['turquoise', 'lilac', 'sage', 'rose']);
    }

    // ─── ظاهر مؤثر سالن ──────────────────────────────────────────

    public function test_a_chosen_template_applies_only_while_the_plan_and_period_allow_it(): void
    {
        $this->onPlan('pro', '1m');
        $this->choose('showcase:rose', 'rail');
        $appearance = SalonAppearance::for($this->salon->fresh());
        $this->assertSame('mahru', $appearance->site, 'حرفه‌ای ۱ ماهه: فقط ۲ چیدمان اول');
        $this->assertTrue($appearance->siteChoiceSuspended());
        $this->assertSame('rail', $appearance->admin);
        $this->assertCount(4, $appearance->allowedSite);

        $this->onPlan('pro', '12m');
        $appearance = SalonAppearance::for($this->salon->fresh());
        $this->assertSame('showcase:rose', $appearance->site, 'حرفه‌ای ۱۲ ماهه: هر ۴ چیدمان × ۲ پالت');
        $this->assertSame(['showcase', 'rose'], [$appearance->layout, $appearance->palette]);
        $this->assertSame('salon-home.showcase', $appearance->homeView());

        // پایان اشتراک: پیش‌فرض، انتخاب سر جایش
        $this->onPlan('pro', '12m', ['subscription_ends_at' => now()->subDay()]);
        $appearance = SalonAppearance::for($this->salon->fresh());
        $this->assertSame(['mahru', 'side'], [$appearance->site, $appearance->admin]);
        $this->assertSame('showcase:rose', $this->salon->fresh()->site_theme);

        // آزمایشی و پایه ۱ ماهه: فقط پیش‌فرض
        $this->onPlan('trial', '1m', ['trial_ends_at' => now()->addDays(5)]);
        $this->assertSame([], SalonAppearance::for($this->salon->fresh())->allowedSite);
        $this->onPlan('basic', '1m');
        $this->assertSame([], SalonAppearance::for($this->salon->fresh())->allowedSite);
        $this->onPlan('basic', '12m');
        $this->assertSame(['signature:rose', 'editorial:rose'], SalonAppearance::for($this->salon->fresh())->allowedSite);

        // سالن پیش از پلن‌ها: همان دسترسی «کامل»
        $this->salon->forceFill(['plan_id' => null, 'subscription_type' => '1m'])->save();
        SalonAppearance::flush();
        $this->assertCount(8, SalonAppearance::for($this->salon->fresh())->allowedSite);
    }

    // ─── صفحه‌های سالن ───────────────────────────────────────────

    public function test_every_site_template_renders_the_salon_home_with_its_layout_and_palette(): void
    {
        $this->get(route('home'))->assertOk()->assertDontSee('data-theme-palette', false)->assertDontSee('dataset.layout', false);

        foreach (ThemeCatalog::siteKeys() as $key) {
            [$layout, $palette] = ThemeCatalog::splitSite($key);
            $this->choose($key);
            $gold = config("themes.palettes.$palette.dark.gold");

            $this->get(route('home'))->assertOk()
                ->assertSee('document.documentElement.dataset.layout="'.$layout.'"', false)
                ->assertSee('data-theme-layout="'.$layout.'"', false)
                ->assertSee("--m-gold:$gold;", false)
                ->assertSee(route('bookings.create'), false);
        }
    }

    public function test_salon_name_that_already_says_salon_is_not_prefixed_twice(): void
    {
        // بسته‌ی «ح»، ۱۰: «درباره ما» و پانویس «سالن زیبایی سالن زیبایی راستا» نشان می‌دادند (نام سالن آزمون خودش «سالن زیبایی راستا» است)
        $this->get(route('home'))->assertOk()->assertSee('سالن زیبایی راستا')->assertDontSee('سالن زیبایی سالن زیبایی');

        foreach (ThemeCatalog::siteKeys() as $key) {
            $this->choose($key);
            $this->get(route('home'))->assertOk()->assertDontSee('سالن زیبایی سالن زیبایی');
        }

        // «خدمات سالن …» و «وبلاگ سالن …» هم «سالن سالن زیبایی راستا» می‌شدند
        $this->get(route('blog.index'))->assertOk()->assertDontSee('سالن سالن')->assertSee('وبلاگ سالن زیبایی راستا');
        $customer = User::factory()->create(['salon_id' => $this->salon->id, 'user_type' => 'customer']);
        $this->actingAs($customer)->get(route('services.index'))->assertOk()->assertDontSee('سالن سالن')->assertSee('خدمات سالن زیبایی راستا');
    }

    public function test_layout_homes_show_real_salon_data_not_invented_numbers(): void
    {
        $this->choose('signature:rose');
        $this->get(route('home'))->assertOk()
            ->assertSee('data-template-home="signature"', false)
            ->assertDontSee('+۸۰۰۰')
            ->assertDontSee('از ۰ نظر')
            ->assertDontSee('سال سابقه');

        $this->choose('app:sage');
        $this->get(route('home'))->assertOk()->assertSee('data-ap-salon-card', false)->assertSee('data-header-book', false)
            ->assertDontSee('از ۰ نظر')->assertDontSee('data-ap-gallery', false);
    }

    public function test_the_preview_parameter_works_only_for_the_salons_own_admins(): void
    {
        $this->get(route('home', ['theme_preview' => 'editorial:lilac']))->assertOk()->assertDontSee('dataset.layout="editorial"', false);

        $this->onPlan('basic', '1m'); // قفل است، ولی پیش‌نمایش باز است
        $this->actingAs($this->owner)->get(route('home', ['theme_preview' => 'editorial:lilac']))->assertOk()
            ->assertSee('dataset.layout="editorial"', false);
        $this->actingAs($this->owner)->get(route('home', ['theme_preview' => 'nope:nope']))->assertOk()
            ->assertDontSee('dataset.layout', false);
    }

    public function test_specialist_panel_follows_the_site_layout(): void
    {
        $specialist = Specialist::factory()->create();
        $user = User::find($specialist->user_id);

        $this->choose('signature:rose');
        $this->actingAs($user)->get(route('specialist.my-dashboard'))->assertOk()
            ->assertDontSee('class="spec-top spec-top--', false)->assertSee('hidden md:block', false);

        foreach (['editorial', 'showcase', 'app'] as $layout) {
            $this->choose("$layout:sage");
            $this->actingAs($user)->get(route('specialist.my-dashboard'))->assertOk()
                ->assertSee('class="spec-top spec-top--'.$layout, false)
                ->assertSee(route('specialist.leaves.index'), false)
                ->assertSee('dataset.layout="'.$layout.'"', false);
        }
    }

    public function test_customer_pages_get_the_palette_and_layout(): void
    {
        $customer = User::factory()->create();
        $this->choose('editorial:turquoise');

        $this->actingAs($customer)->get(route('dashboard'))->assertOk()
            ->assertSee('dataset.layout="editorial"', false)
            ->assertSee('--m-gold:'.config('themes.palettes.turquoise.dark.gold').';', false);
    }

    // ─── پنل مدیر ────────────────────────────────────────────────

    public function test_every_admin_template_shows_the_same_full_menu(): void
    {
        $menuKeys = collect(AdminMenu::groups($this->owner, $this->salon))->flatMap(fn ($g) => array_column($g['items'], 'key'))->all();
        $this->assertContains('appearance', $menuKeys);
        $this->assertGreaterThan(25, count($menuKeys));

        foreach (ThemeCatalog::adminKeys() as $admin) {
            $this->choose(null, $admin === 'side' ? null : $admin);
            $html = $this->actingAs($this->owner)->get(route('admin.dashboard'))->assertOk()->getContent();

            $this->assertStringContainsString('data-admin-shell="'.$admin.'"', $html);
            foreach ($menuKeys as $key) {
                $this->assertStringContainsString('data-menu-key="'.$key.'"', $html, "$admin: $key");
            }
            $tokens = ThemeCatalog::adminPaletteTokens($admin);
            if ($tokens) {
                $this->assertStringContainsString('--m-gold:'.$tokens['dark']['gold'].';', $html, $admin);
            } else {
                $this->assertStringNotContainsString('data-theme-palette', $html);
            }
            // پالت سایت به پنل مدیر نشت نمی‌کند
            $this->assertStringNotContainsString('dataset.layout', $html);
        }
    }

    public function test_menu_keeps_the_permission_rules_of_the_old_sidebar(): void
    {
        $staff = User::factory()->create(['is_admin' => false]);
        $this->salon->admins()->syncWithoutDetaching([$staff->id => ['role' => 'staff']]);

        $keys = collect(AdminMenu::groups($staff, $this->salon))->flatMap(fn ($g) => array_column($g['items'], 'key'))->all();
        $this->assertNotContains('appearance', $keys, 'فقط مالک');
        $this->assertNotContains('salon-settings', $keys);
        $this->assertNotContains('roles', $keys);
        $this->assertContains('dashboard', $keys);
        $this->assertContains('notifications', $keys);
    }

    // ─── «ظاهر سالن» ─────────────────────────────────────────────

    public function test_owner_picks_open_templates_and_locked_ones_are_refused(): void
    {
        $this->onPlan('pro', '1m');

        $this->actingAs($this->owner)->get(route('admin.appearance.edit'))->assertOk()
            ->assertSee('data-site-theme="signature:rose" data-locked="0"', false)
            ->assertSee('data-site-theme="app:turquoise" data-locked="1"', false)
            ->assertSee('data-admin-theme="rail" data-locked="0"', false)
            ->assertSee('data-admin-theme="top" data-locked="1"', false)
            ->assertSee('با ارتقا باز می‌شود');

        $this->actingAs($this->owner)->put(route('admin.appearance.update'), ['site_theme' => 'editorial:sage', 'admin_theme' => 'rail'])
            ->assertRedirect(route('admin.appearance.edit'))->assertSessionHas('success');
        $this->assertSame(['editorial:sage', 'rail'], [$this->salon->fresh()->site_theme, $this->salon->fresh()->admin_theme]);

        $this->actingAs($this->owner)->from(route('admin.appearance.edit'))
            ->put(route('admin.appearance.update'), ['site_theme' => 'app:turquoise', 'admin_theme' => 'dense'])
            ->assertSessionHasErrors(['site_theme', 'admin_theme']);
        $this->assertSame('editorial:sage', $this->salon->fresh()->site_theme);

        $this->actingAs($this->owner)->put(route('admin.appearance.update'), ['site_theme' => 'mahru', 'admin_theme' => 'side'])->assertSessionHas('success');
        $this->assertNull($this->salon->fresh()->site_theme, 'پیش‌فرض null ذخیره می‌شود');
        $this->assertNull($this->salon->fresh()->admin_theme);
    }

    public function test_a_choice_that_became_locked_can_be_kept_and_is_explained(): void
    {
        $this->choose('app:turquoise', 'dense');
        $this->onPlan('basic', '1m');

        $this->actingAs($this->owner)->get(route('admin.appearance.edit'))->assertOk()->assertSee('data-appearance-suspended', false);
        $this->get(route('home'))->assertOk()->assertDontSee('dataset.layout', false);

        $this->actingAs($this->owner)->put(route('admin.appearance.update'), ['site_theme' => 'app:turquoise', 'admin_theme' => 'dense'])
            ->assertSessionHas('success');
        $this->assertSame('app:turquoise', $this->salon->fresh()->site_theme);
    }

    public function test_staff_admin_cannot_open_the_appearance_page(): void
    {
        $staff = User::factory()->create(['is_admin' => true]);
        $this->salon->admins()->syncWithoutDetaching([$staff->id => ['role' => 'staff']]);

        $this->actingAs($staff)->get(route('admin.appearance.edit'))->assertForbidden();
        $this->actingAs($staff)->put(route('admin.appearance.update'), ['site_theme' => 'signature:rose', 'admin_theme' => 'side'])->assertForbidden();
    }

    // ─── اپ‌ها ───────────────────────────────────────────────────

    public function test_the_app_api_carries_the_theme_with_hex_colours(): void
    {
        $this->choose('showcase:lilac');
        $card = CustomerPresenter::salonCard($this->salon->fresh());

        // بسته‌ی ۵الف: key/layout قدیمی برای اپ‌های نصب‌شده، کلید تازه در template
        $this->assertSame('aram:lilac', $card['theme']['key']);
        $this->assertSame('aram', $card['theme']['layout']);
        $this->assertSame('showcase', $card['theme']['template']);
        $this->assertSame('showcase:lilac', $card['theme']['template_key']);
        $this->assertSame('lilac', $card['theme']['palette']);
        $this->assertSame('#C5B3F6', $card['theme']['colors']['dark']['accent']);
        $this->assertSame('#56419E', $card['theme']['colors']['light']['accent']);
        $this->assertSame('#FFFFFF', $card['theme']['colors']['light']['onAccent']);
        $this->assertSame('#8A5A7A', $card['theme']['colors']['light']['accent2']);
        $this->assertSame('#E2B4CF', $card['theme']['colors']['dark']['accent2']);
        $this->assertSame('#FFFFFF', $card['theme']['colors']['light']['onAccent2']);

        $this->choose(null);
        $default = CustomerPresenter::salonCard($this->salon->fresh())['theme'];
        $this->assertSame(['mahru', 'mahru', 'mahru'], [$default['key'], $default['layout'], $default['palette']]);
        $this->assertSame('#D8B96A', $default['colors']['dark']['accent']);
    }

    // ─── ابرمدیر ─────────────────────────────────────────────────

    public function test_super_admin_reorders_layouts_and_palettes(): void
    {
        $super = User::factory()->create(['is_admin' => true, 'user_type' => 'staff']);
        $super->roles()->attach(\App\Models\Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']));
        $super->salons()->detach();
        $payload = Plan::query()->get()->mapWithKeys(fn (Plan $p) => [$p->id => $p->only(Plan::THEME_FIELDS)])->all();

        $this->actingAs($super)->put(route('superadmin.templates.update'), [
            'plans' => $payload,
            'layout_order' => ['showcase', 'signature', 'editorial', 'app'],
            'palette_order' => ['sage', 'rose', 'lilac', 'turquoise'],
        ])->assertSessionHas('success');

        $this->assertSame(['showcase', 'signature', 'editorial', 'app'], ThemeCatalog::layoutOrder());
        $this->assertSame('sage', ThemeCatalog::paletteOrder()[0]);
        $this->assertSame(1, SuperAdminAuditLog::where('action', 'template_access.updated')->count());

        $this->actingAs($super)->from(route('superadmin.templates.index'))->put(route('superadmin.templates.update'), [
            'plans' => $payload,
            'layout_order' => ['showcase', 'showcase', 'editorial', 'app'],
        ])->assertSessionHasErrors('layout_order');
    }

    // ─── بسته‌ی ۵الف: کلیدهای تازه ──────────────────────────────

    public function test_layout_keys_are_the_four_combined_templates_with_their_legacy_names(): void
    {
        $this->assertSame(['signature', 'editorial', 'showcase', 'app'], array_keys((array) config('themes.layouts')));
        $this->assertSame(['شاهکار', 'روزنامه‌ی سالن', 'نمایشگاه', 'اپ'], array_column((array) config('themes.layouts'), 'name'));
        $this->assertSame(['vitrin' => 'signature', 'majalle' => 'editorial', 'aram' => 'showcase', 'bento' => 'app'], ThemeCatalog::legacyLayouts());
        $this->assertSame('signature:rose', ThemeCatalog::normalizeSiteKey('vitrin:rose'));
        $this->assertSame('app:sage', ThemeCatalog::normalizeSiteKey('app:sage'));
        $this->assertSame('mahru', ThemeCatalog::normalizeSiteKey('mahru'));
        $this->assertNull(ThemeCatalog::normalizeSiteKey(null));
        foreach (['signature', 'editorial', 'showcase', 'app'] as $layout) {
            $this->assertTrue(view()->exists('salon-home.'.$layout), $layout);
        }
    }

    public function test_a_legacy_stored_key_still_resolves_to_the_new_template(): void
    {
        $this->choose('bento:turquoise');
        $appearance = SalonAppearance::for($this->salon->fresh());

        $this->assertSame('app:turquoise', $appearance->site);
        $this->assertSame('salon-home.app', $appearance->homeView());
    }

    public function test_the_mapping_migration_renames_stored_keys_and_the_super_admin_order(): void
    {
        $this->choose('majalle:sage');
        $other = Salon::factory()->create(['site_theme' => 'mahru']);
        \App\Models\PlatformSetting::query()->updateOrCreate(['key' => 'theme_layout_order'], ['value' => json_encode(['bento', 'aram', 'majalle', 'vitrin'])]);

        $migration = require database_path('migrations/2026_11_25_000003_map_site_theme_layout_keys.php');
        $migration->up();

        $this->assertSame('editorial:sage', $this->salon->fresh()->site_theme);
        $this->assertSame('mahru', $other->fresh()->site_theme);
        $this->assertSame(['app', 'showcase', 'editorial', 'signature'], ThemeCatalog::layoutOrder());

        $migration->down();
        $this->assertSame('majalle:sage', $this->salon->fresh()->site_theme);
        $this->assertSame('["bento","aram","majalle","vitrin"]', \App\Models\PlatformSetting::query()->where('key', 'theme_layout_order')->value('value'));
    }

    public function test_each_paid_template_gets_its_own_header_and_the_palette_footer(): void
    {
        $this->choose(null);
        $html = $this->get(route('home'))->assertOk()->getContent();
        $this->assertStringNotContainsString('data-palette-footer', $html, 'طلایی ماهرو همان پانویس');
        $this->assertStringNotContainsString('data-emblem-tinted', $html, 'طلایی ماهرو همان نشان طلایی');
        $this->assertStringContainsString('data-salon-app-promo', $html);

        foreach (['signature' => 'fixed top-0 inset-x-0 z-50 sg-hdr', 'app' => 'fixed top-0 inset-x-0 z-50 ap-hdr', 'showcase' => 'fixed top-0 inset-x-0 z-50 sc-hdr', 'editorial' => 'ed-hdr sticky md:static top-0 z-50'] as $layout => $class) {
            $this->choose($layout.':rose');
            $html = $this->get(route('home'))->assertOk()->getContent();
            $this->assertStringContainsString('class="'.$class.'"', $html, $layout);
            $this->assertStringNotContainsString('class="fixed top-0 inset-x-0 z-50"', $html, "$layout: نه سربرگ پیش‌فرض");
            $this->assertStringContainsString('data-palette-footer', $html, $layout);
            if ($layout !== 'editorial') { // سرصفحه‌ی روزنامه فقط نام سالن با نسخ درشت است
                $this->assertStringContainsString('data-emblem-tinted', $html, "$layout: نشان پیش‌فرض به رنگ پالت");
            }
            $this->assertStringContainsString('data-header-book', $html, "$layout: دکمه‌ی رزرو در سربرگ");
            $this->assertStringContainsString('id="mobile-menu"', $html, "$layout: کشوی موبایل");
            $this->assertStringContainsString('--m-foot-bg:', $html);
        }
    }

    public function test_appearance_pages_show_the_new_template_names_and_sketches(): void
    {
        $page = $this->actingAs($this->owner)->get(route('admin.appearance.edit'))->assertOk();
        foreach (['شاهکار', 'روزنامه‌ی سالن', 'نمایشگاه', 'اپ'] as $name) {
            $page->assertSee($name);
        }
        foreach (['signature', 'editorial', 'showcase', 'app'] as $layout) {
            $page->assertSee('mm mm--'.$layout, false);
        }
        $page->assertSee('data-accent2-swatch', false)->assertSee('--mm-ac2:92 58 51;', false);
        $this->assertSame(1, substr_count($page->getContent(), '.mm-sg{'), 'سبک طرح‌ها یک بار');

        $super = User::factory()->create(['is_admin' => true, 'user_type' => 'staff']);
        $super->roles()->attach(\App\Models\Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']));
        $super->salons()->detach();
        $this->actingAs($super)->get(route('superadmin.templates.index'))->assertOk()
            ->assertSee('data-template-layout="signature"', false)->assertSee('data-template-layout="app"', false)
            ->assertSee('mm mm--showcase', false);
    }
}
