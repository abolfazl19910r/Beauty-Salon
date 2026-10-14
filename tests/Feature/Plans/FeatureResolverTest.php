<?php

namespace Tests\Feature\Plans;

use App\Models\Plan;
use App\Models\Salon;
use App\Models\SalonFeatureOverride;
use App\Support\Features\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** قاعده‌ی امکانات (بسته‌ی پلن‌ها): استثنای فعال ← پلن مؤثر ← بی‌پلن = همه؛ و همان قاعده در کوئری (scopeWithFeature). */
class FeatureResolverTest extends TestCase
{
    use RefreshDatabase;

    private function plan(string $key, array $features, array $attributes = []): Plan
    {
        $plan = Plan::create($attributes + ['key' => $key, 'name' => $key, 'price_1m' => 1000000, 'sms_quota_per_month' => 100]);
        $plan->syncFeatures($features);

        return $plan;
    }

    public function test_a_salon_without_a_plan_keeps_every_feature_like_before_plans(): void
    {
        $salon = Salon::factory()->create();

        $this->assertSame(Feature::keys(), $salon->enabledFeatureKeys());
        $this->assertTrue($salon->hasFeature(Feature::SmsReminders));
    }

    public function test_the_plan_decides_and_an_override_beats_the_plan_until_it_expires(): void
    {
        $plan = $this->plan('basic', [Feature::SmsCustomer, Feature::SmsStaff]);
        $salon = Salon::factory()->onPlan($plan)->create();

        $this->assertTrue($salon->hasFeature(Feature::SmsCustomer));
        $this->assertFalse($salon->hasFeature(Feature::SmsReminders));

        SalonFeatureOverride::create(['salon_id' => $salon->id, 'feature' => 'sms_reminders', 'enabled' => true, 'expires_at' => now()->addDays(3)]);
        SalonFeatureOverride::create(['salon_id' => $salon->id, 'feature' => 'sms_staff', 'enabled' => false]);

        $this->assertTrue($salon->hasFeature(Feature::SmsReminders));
        $this->assertFalse($salon->hasFeature(Feature::SmsStaff));

        // کش تا انقضای استثنا معتبر است، نه بیشتر
        $this->travel(4)->days();
        $this->assertFalse($salon->fresh()->hasFeature(Feature::SmsReminders));
        $this->assertFalse($salon->fresh()->hasFeature(Feature::SmsStaff));
    }

    public function test_changing_the_plan_or_its_features_clears_the_cache(): void
    {
        $basic = $this->plan('basic', [Feature::SmsCustomer]);
        $pro = $this->plan('pro', [Feature::SmsCustomer, Feature::Winback]);
        $salon = Salon::factory()->onPlan($basic)->create();

        $this->assertFalse($salon->hasFeature(Feature::Winback));

        $basic->syncFeatures([Feature::SmsCustomer, Feature::Winback]);
        $this->assertTrue($salon->fresh()->hasFeature(Feature::Winback));

        $basic->syncFeatures([Feature::SmsCustomer]);
        $this->assertFalse($salon->fresh()->hasFeature(Feature::Winback));

        $salon->update(['plan_id' => $pro->id]);
        $this->assertTrue($salon->fresh()->hasFeature(Feature::Winback));
    }

    public function test_a_scheduled_next_plan_takes_over_when_its_period_starts(): void
    {
        $pro = $this->plan('pro', [Feature::Winback]);
        $basic = $this->plan('basic', []);
        $salon = Salon::factory()->onPlan($pro)->create(['next_plan_id' => $basic->id, 'next_plan_starts_at' => now()->addDays(10)]);

        $this->assertTrue($salon->hasFeature(Feature::Winback));
        $this->assertSame($pro->id, $salon->effectivePlanId());

        $this->travel(11)->days();
        $salon = $salon->fresh();
        $this->assertSame($basic->id, $salon->effectivePlanId());
        $this->assertFalse($salon->hasFeature(Feature::Winback));

        $this->artisan('plans:apply-scheduled')->assertSuccessful();
        $this->assertSame($basic->id, (int) $salon->fresh()->plan_id);
        $this->assertNull($salon->fresh()->next_plan_id);
    }

    public function test_an_override_on_one_salon_never_touches_another(): void
    {
        $plan = $this->plan('basic', []);
        $a = Salon::factory()->onPlan($plan)->create();
        $b = Salon::factory()->onPlan($plan)->create();

        SalonFeatureOverride::create(['salon_id' => $a->id, 'feature' => 'winback', 'enabled' => true]);

        $this->assertTrue($a->hasFeature(Feature::Winback));
        $this->assertFalse($b->hasFeature(Feature::Winback));
    }

    public function test_the_query_scope_agrees_with_the_resolver_in_every_case(): void
    {
        $with = $this->plan('with', [Feature::Winback]);
        $without = $this->plan('without', []);

        $salons = [
            'legacy' => Salon::factory()->create(),
            'plan_on' => Salon::factory()->onPlan($with)->create(),
            'plan_off' => Salon::factory()->onPlan($without)->create(),
            'forced_on' => Salon::factory()->onPlan($without)->create(),
            'forced_off' => Salon::factory()->onPlan($with)->create(),
            'expired_force' => Salon::factory()->onPlan($without)->create(),
            'next_started' => Salon::factory()->onPlan($without)->create(['next_plan_id' => $with->id, 'next_plan_starts_at' => now()->subHour()]),
            'next_future' => Salon::factory()->onPlan($with)->create(['next_plan_id' => $without->id, 'next_plan_starts_at' => now()->addDay()]),
        ];
        SalonFeatureOverride::create(['salon_id' => $salons['forced_on']->id, 'feature' => 'winback', 'enabled' => true]);
        SalonFeatureOverride::create(['salon_id' => $salons['forced_off']->id, 'feature' => 'winback', 'enabled' => false]);
        SalonFeatureOverride::create(['salon_id' => $salons['expired_force']->id, 'feature' => 'winback', 'enabled' => true, 'expires_at' => now()->subMinute()]);

        $byQuery = Salon::withoutGlobalScopes()->withFeature(Feature::Winback)->pluck('id')->all();

        foreach ($salons as $case => $salon) {
            $this->assertSame($salon->fresh()->hasFeature(Feature::Winback), in_array($salon->id, $byQuery), $case);
        }
        $this->assertEqualsCanonicalizing(
            [$salons['legacy']->id, $salons['plan_on']->id, $salons['forced_on']->id, $salons['next_started']->id, $salons['next_future']->id],
            array_values(array_intersect($byQuery, collect($salons)->pluck('id')->all())),
        );
    }
}
