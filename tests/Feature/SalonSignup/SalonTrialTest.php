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

    public function test_purchase_during_trial_starts_the_paid_period_immediately_and_ends_the_trial(): void
    {
        // ⭐ تصمیم ابوالفضل (۲۰۲۶-۰۹-۲۳): اشتراک از روز خرید شروع می‌شه، نه بعد از پایان آزمایشی.
        $this->signUp();
        $this->travel(5)->days();
        $superAdmin = User::factory()->admin()->create();

        $invoice = app(InvoiceService::class)->recordManualRenewal(
            Salon::where('slug', 'trial-salon')->firstOrFail(), '1m', $superAdmin
        );

        $salon = Salon::where('slug', 'trial-salon')->firstOrFail();
        $this->assertEqualsWithDelta(now()->addMonth()->timestamp, $salon->subscription_ends_at->timestamp, 5);
        $this->assertEqualsWithDelta(now()->timestamp, $invoice->period_start->timestamp, 5);
        $this->assertEqualsWithDelta(now()->timestamp, $salon->trial_ends_at->timestamp, 5);
        $this->assertFalse($salon->isOnTrial());
        $this->assertSame(0, $salon->trialDaysLeft());
        $this->assertNull($salon->sms_quota_per_month);
    }

    public function test_online_gateway_purchase_during_trial_also_starts_immediately(): void
    {
        $salon = $this->signUp();
        $this->travel(3)->days();

        $invoice = \App\Models\Invoice::factory()->create([
            'salon_id' => $salon->id,
            'subscription_type' => '6m',
            'status' => 'pending',
        ]);

        $paid = app(InvoiceService::class)->markPaidFromGateway($invoice, 'REF-TRIAL');

        $salon = $salon->fresh();
        $this->assertEqualsWithDelta(now()->addMonths(6)->timestamp, $salon->subscription_ends_at->timestamp, 5);
        $this->assertEqualsWithDelta(now()->timestamp, $paid->period_start->timestamp, 5);
        $this->assertFalse($salon->isOnTrial());
    }

    public function test_purchase_after_trial_expired_also_starts_today(): void
    {
        $this->signUp();
        $this->travel(20)->days();
        $superAdmin = User::factory()->admin()->create();

        app(InvoiceService::class)->recordManualRenewal(Salon::where('slug', 'trial-salon')->firstOrFail(), '3m', $superAdmin);

        $salon = Salon::where('slug', 'trial-salon')->firstOrFail();
        $this->assertEqualsWithDelta(now()->addMonths(3)->timestamp, $salon->subscription_ends_at->timestamp, 5);
    }

    public function test_second_purchase_after_trial_still_stacks_on_the_paid_period(): void
    {
        // خارج از آزمایشی، تمدید مثل قبل روی پایان دوره‌ی پولی فعلی سوار می‌شه.
        $this->signUp();
        $superAdmin = User::factory()->admin()->create();
        app(InvoiceService::class)->recordManualRenewal(Salon::where('slug', 'trial-salon')->firstOrFail(), '1m', $superAdmin);
        $firstEnd = Salon::where('slug', 'trial-salon')->firstOrFail()->subscription_ends_at->copy();

        app(InvoiceService::class)->recordManualRenewal(Salon::where('slug', 'trial-salon')->firstOrFail(), '1m', $superAdmin);

        $this->assertEqualsWithDelta(
            $firstEnd->addMonth()->timestamp,
            Salon::where('slug', 'trial-salon')->firstOrFail()->subscription_ends_at->timestamp,
            5
        );
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
            ->assertSee('۱۴ روز رایگان', false);

        $this->get(route('salon-signup.create', ['plan' => 'bogus']))
            ->assertViewHas('selectedPlan', '1m');
    }
}
