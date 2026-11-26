<?php

namespace Tests\Feature\Plans;

use App\Models\BeautyService;
use App\Models\Plan;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Services\Plans\DefaultPlans;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * بسته‌ی ۰ (تصمیم ۲۰۲۶-۱۱-۲۴): دوره‌ی آزمایشی رایگان حداکثر ۲ کارشناس (قبلاً ۱۰). سقف واقعی سالن آزمایشی کمترینِ عدد فرم
 * ثبت‌نام (max_specialists_count، تا ۵۰) و included_specialists پلن آزمایشی است؛ بعد از خرید همان عدد ثبت‌نام.
 */
class TrialSpecialistLimitTest extends TestCase
{
    use RefreshDatabase;

    private function trialSalon(int $signupCount = 8, array $extra = []): Salon
    {
        app(DefaultPlans::class)->sync();

        $salon = Salon::factory()->onPlan(Plan::trial())->create($extra + [
            'max_specialists_count' => $signupCount,
            'trial_ends_at' => now()->addDays(10),
            'subscription_ends_at' => now()->addDays(10),
        ]);
        app(CurrentSalon::class)->set($salon);

        return $salon;
    }

    private function addSpecialist(string $phone)
    {
        $admin = User::factory()->create(['is_admin' => true]);

        return $this->actingAs($admin)->post('/admin/specialists', [
            'name' => 'کارشناس تازه',
            'phone' => $phone,
            'services' => [BeautyService::factory()->create()->id],
        ]);
    }

    public function test_the_default_trial_plan_includes_two_experts(): void
    {
        app(DefaultPlans::class)->sync();

        $this->assertSame(2, (int) Plan::trial()->included_specialists);
    }

    public function test_sync_lowers_a_trial_plan_still_on_the_old_default_but_keeps_a_manual_value(): void
    {
        app(DefaultPlans::class)->sync();
        Plan::trial()->update(['included_specialists' => DefaultPlans::LEGACY_TRIAL_SPECIALISTS]);

        $this->assertTrue(app(DefaultPlans::class)->sync()['trial_specialists_updated']);
        $this->assertSame(2, (int) Plan::trial()->fresh()->included_specialists);

        Plan::trial()->update(['included_specialists' => 4]);
        $this->assertFalse(app(DefaultPlans::class)->sync()['trial_specialists_updated']);
        $this->assertSame(4, (int) Plan::trial()->fresh()->included_specialists);
    }

    public function test_a_trial_salon_cannot_add_a_third_expert_even_with_a_bigger_signup_count(): void
    {
        $salon = $this->trialSalon(8);
        Specialist::factory()->count(2)->create();

        $this->assertSame(2, $salon->fresh()->specialistLimit());

        $this->addSpecialist('09121234590')->assertRedirect()
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'دوره‌ی آزمایشی رایگان حداکثر ۲ کارشناس'));
        $this->assertSame(2, Specialist::count());
    }

    public function test_a_trial_salon_can_add_its_second_expert(): void
    {
        $this->trialSalon(8);
        Specialist::factory()->create();

        $this->addSpecialist('09121234591')->assertRedirect(route('admin.specialists.index'))->assertSessionHas('success');
        $this->assertSame(2, Specialist::count());
    }

    public function test_the_signup_count_applies_once_the_salon_leaves_the_trial(): void
    {
        $salon = $this->trialSalon(8);
        $this->assertSame(2, $salon->specialistLimit());

        $salon->update(['plan_id' => Plan::byKey('pro')->id, 'trial_ends_at' => null]);
        $this->assertSame(8, $salon->fresh()->specialistLimit());

        $expired = $this->trialSalon(8, ['trial_ends_at' => now()->subDay()]);
        $this->assertSame(8, $expired->specialistLimit());

        $small = $this->trialSalon(1);
        $this->assertSame(1, $small->specialistLimit(), 'عدد ثبت‌نام کوچک‌تر همان سقف است');
    }

    public function test_signup_page_and_landing_say_two_experts_in_the_trial(): void
    {
        app(DefaultPlans::class)->sync();

        $this->get(route('salon-signup.create'))->assertOk()
            ->assertSee('data-trial-specialists="2"', false)
            ->assertSee('حداکثر ۲ کارشناس');

        $html = $this->get(route('central.home'))->assertOk()->getContent();
        // بسته‌ی ۶: کارت آزمایشی جدا برداشته شد؛ خط زیر تیتر تعرفه‌ها
        $this->assertStringContainsString('۱۴ روز رایگان با ۲ کارشناس و ۴۰۰ قطعه پیامک', $html);
    }
}
