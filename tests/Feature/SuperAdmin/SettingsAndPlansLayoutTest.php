<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Plan;
use App\Models\Role;
use App\Models\User;
use App\Services\Plans\DefaultPlans;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * بسته‌ی ۱ ظاهری (۲۰۲۶-۱۱-۲۴): تنظیمات پلتفرم یک ستون با فهرست بخش‌ها؛ صفحه‌ی پلن‌ها قاعده‌ی سقف آزمایشی را می‌گوید
 * و اگر پلن آزمایشی هنوز روی ۱۰ قدیمی است هشدار می‌دهد.
 */
class SettingsAndPlansLayoutTest extends TestCase
{
    use RefreshDatabase;

    private User $super;

    protected function setUp(): void
    {
        parent::setUp();
        app(CurrentSalon::class)->clear();
        $this->super = User::factory()->create(['is_admin' => true, 'user_type' => 'staff', 'salon_id' => null]);
        $this->super->roles()->attach(Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']));
    }

    public function test_settings_page_is_one_column_with_a_section_index(): void
    {
        $html = $this->actingAs($this->super)->get(route('superadmin.settings.edit'))->assertOk()->getContent();

        $this->assertStringContainsString('data-settings-index', $html);
        foreach (['contact-settings', 'legal-settings', 'domain-verification', 'push-settings'] as $id) {
            $this->assertStringContainsString('href="#'.$id.'"', $html);
            $this->assertStringContainsString('id="'.$id.'"', $html);
        }
        $this->assertStringNotContainsString('xl:grid-cols-2', $html);
        // دکمه‌ی فایل فارسی (بسته‌ی ۰) سر جایش است
        $this->assertStringContainsString('data-ui-file', $html);
        $this->assertStringNotContainsString('type="file" name="verification_file" class', $html);
    }

    public function test_plans_page_explains_the_trial_cap_and_warns_when_not_synced(): void
    {
        app(DefaultPlans::class)->sync();
        $trial = Plan::trial();

        $html = $this->actingAs($this->super)->get(route('superadmin.plans.index'))->assertOk()->getContent();
        $this->assertStringContainsString('data-trial-cap-note', $html);
        $this->assertStringNotContainsString('data-trial-stale', $html);
        $this->assertStringContainsString('data-plan="trial"', $html);

        $trial->update(['included_specialists' => DefaultPlans::LEGACY_TRIAL_SPECIALISTS]);
        $this->actingAs($this->super)->get(route('superadmin.plans.index'))
            ->assertSee('data-trial-stale', false)->assertSee('plans:sync-defaults');

        $this->actingAs($this->super)->get(route('superadmin.plans.edit', $trial))->assertOk()
            ->assertSee('data-trial-cap-help', false)->assertSee('کمترینِ عدد ثبت‌نام و این عدد');
    }
}
