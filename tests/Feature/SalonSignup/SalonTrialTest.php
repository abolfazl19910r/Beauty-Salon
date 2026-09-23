<?php

namespace Tests\Feature\SalonSignup;

use App\Models\Salon;
use App\Models\User;
use App\Services\Payment\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ فیچر «دوره‌ی آزمایشی رایگان» (۲۰۲۶-۰۹-۲۳). سالن ثبت‌نام‌شده از صفحه‌ی عمومی، به‌جای «از همون
 * لحظه منقضی»، billing.trial_days روز کاملاً فعاله؛ بعد از اون همون EnsureAdminSalonActive /
 * ResolveSalonFromRoute موجود بدون هیچ کد جدیدی دسترسی رو می‌بندن. مسیر «بدون آزمایشی» در
 * SalonSignupTest مستند مونده.
 */
class SalonTrialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['billing.trial_days' => 14, 'billing.trial_sms_quota' => 300]);
    }

    private function signUp(array $overrides = []): Salon
    {
        $this->post(route('salon-signup.store'), array_merge([
            'name' => 'سالن آزمایشی',
            'slug' => 'trial-salon',
            'subscription_type' => '3m',
            'owner_name' => 'مریم کریمی',
            'owner_phone' => '09125556677',
            'owner_password' => 'Str0ng!Passw0rd',
            'owner_password_confirmation' => 'Str0ng!Passw0rd',
        ], $overrides))->assertRedirect(route('salon-signup.verify'));

        return Salon::where('slug', 'trial-salon')->firstOrFail();
    }

    private function verifyOtp(): User
    {
        $owner = User::where('phone', '09125556677')->firstOrFail();
        $this->post(route('salon-signup.verify.store'), ['code' => $owner->fresh()->verification_code]);

        return $owner->fresh();
    }

    public function test_signup_creates_an_active_trial_salon_with_reduced_sms_quota(): void
    {
        $salon = $this->signUp();

        $this->assertTrue($salon->subscription_ends_at->isFuture());
        $this->assertNotNull($salon->trial_ends_at);
        $this->assertEqualsWithDelta(now()->addDays(14)->timestamp, $salon->trial_ends_at->timestamp, 5);
        $this->assertSame(300, (int) $salon->sms_quota_per_month);
        $this->assertTrue($salon->isOnTrial());
        $this->assertSame(14, $salon->trialDaysLeft());
        $this->assertTrue($salon->isTrialSmsQuotaInEffect());
    }

    public function test_verified_trial_owner_lands_on_the_dashboard_not_billing(): void
    {
        $this->signUp();

        $owner = User::where('phone', '09125556677')->firstOrFail();
        $response = $this->post(route('salon-signup.verify.store'), ['code' => $owner->fresh()->verification_code]);

        $response->assertRedirect(route('admin.home'));
        $this->assertAuthenticatedAs($owner->fresh());
        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_public_salon_page_is_live_during_trial(): void
    {
        $this->signUp();

        $this->get('/s/trial-salon')->assertOk();
    }

    public function test_after_trial_ends_panel_is_restricted_to_billing_and_public_page_404s(): void
    {
        $this->signUp();
        $this->verifyOtp();

        $this->travel(15)->days();

        $salon = Salon::where('slug', 'trial-salon')->firstOrFail();
        $this->assertFalse($salon->isOnTrial());
        $this->assertSame(0, $salon->trialDaysLeft());

        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.billing.index'));
        $this->get(route('admin.billing.index'))->assertOk();
        $this->get('/s/trial-salon')->assertNotFound();
    }

    public function test_first_purchase_during_trial_keeps_remaining_days_and_restores_normal_sms_quota(): void
    {
        $salon = $this->signUp();
        $trialEnd = $salon->trial_ends_at->copy();
        $superAdmin = User::factory()->admin()->create();

        app(InvoiceService::class)->recordManualRenewal($salon->fresh(), '1m', $superAdmin);

        $salon = $salon->fresh();
        $this->assertNull($salon->sms_quota_per_month);
        $this->assertFalse($salon->isOnTrial());
        $this->assertEqualsWithDelta($trialEnd->copy()->addMonth()->timestamp, $salon->subscription_ends_at->timestamp, 5);
    }

    public function test_purchase_never_clears_a_manual_super_admin_sms_override(): void
    {
        $salon = $this->signUp();
        $salon->update(['sms_quota_per_month' => 5000]);
        $superAdmin = User::factory()->admin()->create();

        app(InvoiceService::class)->recordManualRenewal($salon->fresh(), '1m', $superAdmin);

        $this->assertSame(5000, (int) $salon->fresh()->sms_quota_per_month);
    }

    public function test_super_admin_created_salons_never_count_as_trial(): void
    {
        $salon = Salon::factory()->create();

        $this->assertNull($salon->trial_ends_at);
        $this->assertFalse($salon->isOnTrial());
        $this->assertFalse($salon->isTrialSmsQuotaInEffect());
    }

    public function test_signup_form_preselects_plan_from_query_string_and_mentions_trial(): void
    {
        $this->get(route('salon-signup.create', ['plan' => '6m']))
            ->assertOk()
            ->assertViewHas('selectedPlan', '6m')
            ->assertSee('14 روز', false);

        $this->get(route('salon-signup.create', ['plan' => 'bogus']))
            ->assertViewHas('selectedPlan', '1m');
    }
}
