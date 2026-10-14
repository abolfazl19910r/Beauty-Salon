<?php

namespace Tests\Feature\Plans;

use App\Models\Plan;
use App\Models\Role;
use App\Models\Salon;
use App\Models\SalonFeatureOverride;
use App\Models\User;
use App\Services\Plans\DefaultPlans;
use App\Support\Features\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/** صفحه‌ی «پلن‌ها و امکانات» سوپرادمین و پلن/استثنای هر سالن (بسته‌ی پلن‌ها، ۲۰۲۶-۱۰-۱۴) */
class SuperAdminPlansTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']);
        $this->superAdmin = User::factory()->create(['is_admin' => true]);
        $this->superAdmin->roles()->attach($role);
        app(DefaultPlans::class)->sync();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'key' => 'vip', 'name' => 'ویژه', 'description' => 'برای سالن‌های بزرگ', 'is_active' => '1', 'is_public' => '1', 'sort' => '50',
            'price_1m' => '۳٬۰۰۰٬۰۰۰', 'price_3m' => '8,500,000', 'price_6m' => '', 'price_12m' => '30000000',
            'included_specialists' => '20', 'extra_specialist_price_per_month' => '200000', 'sms_quota_per_month' => '5000',
            'features' => ['sms_reminders', 'sms_customer', 'treatment_courses'],
        ], $overrides);
    }

    public function test_the_index_and_forms_render_for_every_plan(): void
    {
        $this->actingAs($this->superAdmin)->get(route('superadmin.plans.index'))->assertOk()->assertSee('کلینیک')->assertSee('آزمایشی');
        $this->actingAs($this->superAdmin)->get(route('superadmin.plans.create'))->assertOk();
        foreach (Plan::all() as $plan) {
            $this->actingAs($this->superAdmin)->get(route('superadmin.plans.edit', $plan))->assertOk()->assertSee('یادآوری پیامکی پیش از نوبت');
        }
    }

    public function test_a_plan_is_created_with_prices_features_and_an_unsold_period(): void
    {
        $this->actingAs($this->superAdmin)->post(route('superadmin.plans.store'), $this->payload())->assertRedirect(route('superadmin.plans.index'));

        $plan = Plan::byKey('vip');
        $this->assertSame(3000000, $plan->price_1m);
        $this->assertSame(8500000, $plan->price_3m);
        $this->assertNull($plan->price_6m);
        $this->assertSame(['1m', '3m', '12m'], $plan->periods());
        $this->assertEqualsCanonicalizing(['sms_reminders', 'sms_customer', 'treatment_courses'], $plan->featureKeys());
        $this->assertTrue(Activity::where('description', 'plan_created')->exists());
    }

    public function test_validation_refuses_a_plan_without_any_price_and_a_duplicate_key(): void
    {
        $this->actingAs($this->superAdmin)->post(route('superadmin.plans.store'), $this->payload(['price_1m' => '', 'price_3m' => '', 'price_12m' => '']))
            ->assertSessionHasErrors('price_1m');
        $this->actingAs($this->superAdmin)->post(route('superadmin.plans.store'), $this->payload(['key' => 'full']))
            ->assertSessionHasErrors('key');
    }

    public function test_unticking_reminders_on_the_trial_plan_reports_affected_salons_and_takes_effect(): void
    {
        $trial = Plan::trial();
        $salon = Salon::factory()->onPlan($trial)->create(['trial_ends_at' => now()->addDays(5)]);
        $this->assertTrue($salon->hasFeature(Feature::SmsReminders));

        $features = array_values(array_diff($trial->featureKeys(), ['sms_reminders']));
        $this->actingAs($this->superAdmin)->put(route('superadmin.plans.update', $trial), [
            'key' => 'trial', 'name' => 'آزمایشی', 'is_active' => '1', 'trial_days' => '14',
            'included_specialists' => '10', 'extra_specialist_price_per_month' => '0', 'sms_quota_per_month' => '400',
            'features' => $features,
        ])->assertRedirect(route('superadmin.plans.edit', $trial))
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'یادآوری پیامکی پیش از نوبت'));

        $this->assertFalse($salon->fresh()->hasFeature(Feature::SmsReminders));
        $this->assertTrue($salon->fresh()->hasFeature(Feature::SmsCustomer));
    }

    public function test_a_trial_plan_cannot_get_prices(): void
    {
        $trial = Plan::trial();
        $this->actingAs($this->superAdmin)->put(route('superadmin.plans.update', $trial), [
            'key' => 'trial', 'name' => 'آزمایشی', 'trial_days' => '14', 'price_1m' => '100',
            'included_specialists' => '10', 'extra_specialist_price_per_month' => '0', 'sms_quota_per_month' => '400',
        ])->assertSessionHasErrors('price_1m');
    }

    public function test_a_plan_with_salons_cannot_be_deleted_but_an_empty_one_can(): void
    {
        $basic = Plan::byKey('basic');
        Salon::factory()->onPlan($basic)->create();

        $this->actingAs($this->superAdmin)->delete(route('superadmin.plans.destroy', $basic))->assertSessionHas('error');
        $this->assertNotNull(Plan::byKey('basic'));

        $clinic = Plan::byKey('clinic');
        $this->actingAs($this->superAdmin)->delete(route('superadmin.plans.destroy', $clinic))->assertRedirect(route('superadmin.plans.index'));
        $this->assertNull(Plan::byKey('clinic'));
    }

    public function test_assigning_a_plan_by_hand_logs_it_and_cancels_a_scheduled_change(): void
    {
        $salon = Salon::factory()->onPlan(Plan::byKey('full'))->create(['next_plan_id' => Plan::byKey('basic')->id, 'next_plan_starts_at' => now()->addDays(3)]);
        $clinic = Plan::byKey('clinic');

        $this->actingAs($this->superAdmin)->get(route('superadmin.salons.edit', $salon))->assertOk()->assertSee('data-testid="salon-plan"', false);
        $this->actingAs($this->superAdmin)->put(route('superadmin.salons.plan', $salon), ['plan_id' => $clinic->id])
            ->assertRedirect(route('superadmin.salons.edit', $salon));

        $salon->refresh();
        $this->assertSame($clinic->id, (int) $salon->plan_id);
        $this->assertNull($salon->next_plan_id);
        $this->assertTrue($salon->hasFeature(Feature::TreatmentCourses));
        $this->assertTrue(Activity::where('description', 'salon_plan_changed')->where('subject_id', $salon->id)->exists());
    }

    public function test_overrides_are_added_with_a_jalali_expiry_and_removed(): void
    {
        $salon = Salon::factory()->onPlan(Plan::byKey('basic'))->create();
        $this->assertFalse($salon->hasFeature(Feature::Winback));
        $expiry = verta(now()->addDays(30))->format('Y/m/d');

        $this->actingAs($this->superAdmin)->post(route('superadmin.salons.feature-overrides.store', $salon), [
            'feature' => 'winback', 'enabled' => '1', 'expires_at' => $expiry, 'note' => 'یک ماه امتحانی',
        ])->assertRedirect(route('superadmin.salons.edit', $salon));

        $override = SalonFeatureOverride::firstOrFail();
        $this->assertSame($this->superAdmin->id, $override->set_by);
        $this->assertTrue($override->expires_at->isFuture());
        $this->assertTrue($salon->fresh()->hasFeature(Feature::Winback));

        $this->actingAs($this->superAdmin)->post(route('superadmin.salons.feature-overrides.store', $salon), [
            'feature' => 'winback', 'enabled' => '1', 'expires_at' => '1400/01/01',
        ])->assertSessionHasErrors('expires_at');

        $this->actingAs($this->superAdmin)->delete(route('superadmin.salons.feature-overrides.destroy', [$salon, $override]))->assertRedirect();
        $this->assertFalse($salon->fresh()->hasFeature(Feature::Winback));
    }

    public function test_an_override_of_another_salon_cannot_be_removed_through_this_salon(): void
    {
        $a = Salon::factory()->create();
        $b = Salon::factory()->create();
        $override = SalonFeatureOverride::create(['salon_id' => $b->id, 'feature' => 'winback', 'enabled' => false]);

        $this->actingAs($this->superAdmin)->delete(route('superadmin.salons.feature-overrides.destroy', [$a, $override]))->assertNotFound();
        $this->assertNotNull($override->fresh());
    }

    public function test_salon_list_filters_by_plan_and_only_super_admins_reach_the_plans_page(): void
    {
        $onBasic = Salon::factory()->onPlan(Plan::byKey('basic'))->create(['name' => 'سالن پایه‌ای']);
        Salon::factory()->onPlan(Plan::byKey('full'))->create(['name' => 'سالن کامل‌پلن']);

        $this->actingAs($this->superAdmin)->get(route('superadmin.salons.index', ['plan_id' => $onBasic->plan_id]))
            ->assertOk()->assertSee('سالن پایه‌ای')->assertDontSee('سالن کامل‌پلن');

        $this->actingAs(User::factory()->create(['is_admin' => true]))->get(route('superadmin.plans.index'))->assertForbidden();
    }
}
