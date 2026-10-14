<?php

namespace Tests\Feature\Plans;

use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Salon;
use App\Models\User;
use App\Services\Payment\InvoiceService;
use App\Services\Plans\DefaultPlans;
use App\Services\Plans\PlanBilling;
use App\Services\Sms\SmsQuotaService;
use App\Support\Billing\SubscriptionPricing;
use App\Support\CurrentSalon;
use App\Support\Features\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CompletesSalonSignup;
use Tests\Concerns\SalonContactPayload;
use Tests\TestCase;

/** خرید، تمدید، تنزل و ارتقای پلن (بسته‌ی پلن‌ها، تصمیم‌های ۲۰۲۶-۱۰-۱۴) */
class PlanBillingTest extends TestCase
{
    use CompletesSalonSignup;
    use RefreshDatabase;
    use SalonContactPayload;

    private function plan(string $key, array $prices, array $features = [], array $attributes = []): Plan
    {
        $plan = Plan::create($attributes + [
            'key' => $key, 'name' => $key, 'is_active' => true, 'is_public' => true,
            'price_1m' => $prices[0], 'price_3m' => $prices[1] ?? null, 'price_6m' => $prices[2] ?? null, 'price_12m' => $prices[3] ?? null,
            'included_specialists' => 5, 'extra_specialist_price_per_month' => 100000, 'sms_quota_per_month' => 1000,
        ]);
        $plan->syncFeatures($features);

        return $plan;
    }

    private function salon(): Salon
    {
        return app(CurrentSalon::class)->get();
    }

    private function fakeGateway(): void
    {
        Http::fake([
            '*request.json' => Http::response(['data' => ['code' => 100, 'authority' => 'PLANAUTH']], 200),
            '*verify.json' => Http::response(['data' => ['code' => 100, 'ref_id' => 'REF1']], 200),
        ]);
    }

    // ─── پلن‌های پیش‌فرض: بدون هیچ تغییر رفتاری ─────────────────────────

    public function test_default_plans_mirror_todays_config_and_attach_salons_without_changing_anything(): void
    {
        config(['billing.trial_sms_quota' => 400]);
        $paid = $this->salon();
        $paid->update(['max_specialists_count' => 12]);
        $pricing = app(SubscriptionPricing::class);
        $before = collect(SubscriptionPricing::PLAN_MONTHS)->keys()->mapWithKeys(fn ($t) => [$t => $pricing->price($t, 12)])->all();
        $trial = Salon::factory()->create(['trial_ends_at' => now()->addDays(5), 'subscription_ends_at' => now()->addDays(5), 'sms_quota_per_month' => 400]);

        $result = app(DefaultPlans::class)->sync();

        $this->assertSame(['trial', 'full', 'basic', 'pro', 'clinic'], $result['created']);
        $paid->refresh();
        $trial->refresh();
        $this->assertSame('full', $paid->plan->key);
        $this->assertSame('trial', $trial->plan->key);
        $this->assertNull($trial->sms_quota_per_month); // حالا از خود پلن آزمایشی، همان ۴۰۰
        $this->assertSame(400, app(SmsQuotaService::class)->quotaFor($trial));
        $this->assertSame((int) config('billing.sms_quota_per_month'), app(SmsQuotaService::class)->quotaFor($paid));

        $after = collect(SubscriptionPricing::PLAN_MONTHS)->keys()->mapWithKeys(fn ($t) => [$t => $pricing->price($t, 12, $paid->plan)])->all();
        $this->assertSame($before, $after);

        // همه‌ی امکاناتی که امروز وجود دارند؛ دو امکانِ هنوز ساخته‌نشده نه
        $this->assertSame([], array_diff(Feature::keys(), $paid->enabledFeatureKeys(), ['winback_manual', 'treatment_courses']));
        $this->assertSame($paid->enabledFeatureKeys(), $trial->enabledFeatureKeys());

        // دوباره اجرا: هیچ چیز تازه‌ای
        $this->assertSame([], app(DefaultPlans::class)->sync()['created']);
        $this->artisan('plans:sync-defaults')->assertSuccessful();
        $this->assertSame(5, Plan::count());

        // هر پلن پولی چهار بازه دارد
        foreach (Plan::where('is_trial', false)->get() as $plan) {
            $this->assertSame(['1m', '3m', '6m', '12m'], $plan->periods(), $plan->key);
        }
    }

    // ─── قیمت ────────────────────────────────────────────────────────

    public function test_price_per_plan_period_and_extra_specialists(): void
    {
        $plan = $this->plan('pro', [1000000, 2700000, 5100000, 9000000]);
        $pricing = app(SubscriptionPricing::class);

        $this->assertSame(1000000, $pricing->price('1m', 5, $plan));
        $this->assertSame(1100000, $pricing->price('1m', 6, $plan));
        // ۳ ماهه: ۲ متخصص اضافه × ۱۰۰ هزار × ۳ ماه × نسبت ۰٫۹
        $this->assertSame(2700000 + 540000, $pricing->price('3m', 7, $plan));

        $plan->update(['price_6m' => null]);
        $this->assertFalse($pricing->sells('6m', $plan->fresh()));
        $this->assertSame(['1m', '3m', '12m'], $plan->fresh()->periods());
    }

    public function test_the_invoice_snapshot_does_not_change_when_the_plan_changes_later(): void
    {
        $this->fakeGateway();
        $plan = $this->plan('pro', [1000000, 2700000], [Feature::SmsCustomer]);
        $salon = $this->salon();
        $salon->update(['plan_id' => $plan->id, 'max_specialists_count' => 5]);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post(route('admin.billing.purchase'), ['choice' => "{$plan->id}:3m"])->assertRedirect();

        $plan->update(['price_3m' => 9999000, 'name' => 'نام تازه']);
        $plan->syncFeatures([]);

        $invoice = Invoice::first();
        $this->assertSame(2700000, $invoice->amount);
        $this->assertSame($plan->id, $invoice->plan_id);
        $this->assertSame('pro', $invoice->planName());
        $this->assertSame(2700000, $invoice->plan_snapshot['prices']['3m']);
        $this->assertSame(['sms_customer'], $invoice->plan_snapshot['features']);
    }

    // ─── خرید: آزمایشی فوری، وسط دوره از پایان دوره ─────────────────────

    public function test_buying_another_plan_on_trial_switches_immediately(): void
    {
        $trialPlan = $this->plan('trial', [1], [], ['is_trial' => true, 'is_public' => false, 'price_1m' => null, 'trial_days' => 14]);
        $pro = $this->plan('pro', [1000000]);
        $salon = Salon::factory()->onPlan($trialPlan)->create(['trial_ends_at' => now()->addDays(10), 'subscription_ends_at' => now()->addDays(10)]);

        app(InvoiceService::class)->markPaidFromGateway(
            app(InvoiceService::class)->createPendingOnlinePurchase($salon, '1m', null, $pro), 'R1');

        $salon->refresh();
        $this->assertSame($pro->id, (int) $salon->plan_id);
        $this->assertNull($salon->next_plan_id);
        $this->assertFalse($salon->isOnTrial());
    }

    public function test_buying_a_different_plan_mid_term_takes_effect_at_the_end_of_the_paid_period(): void
    {
        $pro = $this->plan('pro', [1500000]);
        $basic = $this->plan('basic', [900000]);
        $salon = Salon::factory()->onPlan($pro)->create(['subscription_type' => '1m', 'subscription_ends_at' => now()->addDays(20), 'max_specialists_count' => 5]);
        $end = $salon->subscription_ends_at->copy();

        $invoice = app(InvoiceService::class)->createPendingOnlinePurchase($salon, '1m', null, $basic);
        $this->assertSame(900000, $invoice->amount);
        app(InvoiceService::class)->markPaidFromGateway($invoice, 'R2');

        $salon->refresh();
        $this->assertSame($pro->id, $salon->effectivePlanId());
        $this->assertSame($basic->id, (int) $salon->next_plan_id);
        $this->assertEqualsWithDelta($end->timestamp, $salon->next_plan_starts_at->timestamp, 2);
        $this->assertEqualsWithDelta($end->copy()->addMonth()->timestamp, $salon->subscription_ends_at->timestamp, 2);

        // فقط یک تغییر زمان‌بندی‌شده: خرید پلن سوم رد می‌شود، تمدید همان پلن بعدی مجاز است
        $other = $this->plan('other', [700000]);
        $this->assertNotNull(app(PlanBilling::class)->purchaseError($salon, $other, '1m'));
        $this->assertNull(app(PlanBilling::class)->purchaseError($salon, $basic, '1m'));

        $this->travelTo($end->copy()->addHour());
        $this->assertSame($basic->id, $salon->fresh()->effectivePlanId());
    }

    // ─── پلن غیرفعال/غیرعمومی ─────────────────────────────────────────

    public function test_an_inactive_plan_is_only_renewable_by_salons_already_on_it(): void
    {
        $old = $this->plan('old', [800000], [], ['is_active' => false]);
        $this->plan('pro', [1500000]);
        $onOld = Salon::factory()->onPlan($old)->create(['subscription_ends_at' => now()->addDays(5), 'max_specialists_count' => 5]);
        $newcomer = Salon::factory()->create(['subscription_ends_at' => now()->subDay()]);

        $this->assertNull(app(PlanBilling::class)->purchaseError($onOld, $old, '1m'));
        $this->assertNotNull(app(PlanBilling::class)->purchaseError($newcomer, $old, '1m'));
        $this->assertFalse(app(PlanBilling::class)->purchasablePlans($newcomer)->contains('id', $old->id));
        $this->assertSame(800000, app(InvoiceService::class)->createPendingOnlinePurchase($onOld, '1m', null)->amount);

        $admin = User::factory()->create(['is_admin' => true]);
        app(CurrentSalon::class)->get()->update(['plan_id' => null]);
        $this->actingAs($admin)->post(route('admin.billing.purchase'), ['choice' => "{$old->id}:1m"])
            ->assertSessionHasErrors('error');
        $this->assertSame(0, Invoice::where('salon_id', app(CurrentSalon::class)->id())->count());
    }

    // ─── ارتقای وسط دوره (تصمیم ۳ الف) ───────────────────────────────

    public function test_mid_term_upgrade_charges_the_daily_difference_and_keeps_the_end_date(): void
    {
        $this->fakeGateway();
        $basic = $this->plan('basic', [900000, 2700000]);
        $pro = $this->plan('pro', [1500000, 4500000]);
        $salon = $this->salon();
        $salon->update(['plan_id' => $basic->id, 'subscription_type' => '3m', 'subscription_ends_at' => now()->addDays(30)->addMinute(), 'max_specialists_count' => 5]);
        $end = $salon->fresh()->subscription_ends_at->copy();

        // (۴٬۵۰۰٬۰۰۰ − ۲٬۷۰۰٬۰۰۰)/۳ ماه/۳۰ روز × ۳۱ روز (روز جزئی گرد به بالا) = ۶۲۰٬۰۰۰
        $this->assertSame(620000, app(SubscriptionPricing::class)->upgradePrice($salon->fresh(), $pro));
        $this->assertNotNull(app(PlanBilling::class)->upgradeError($salon->fresh(), $basic->fresh()), 'same plan');

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->post(route('admin.billing.upgrade'), ['plan_id' => $pro->id])->assertRedirect();

        $invoice = Invoice::firstOrFail();
        $this->assertSame('upgrade', $invoice->kind);
        $this->assertSame(620000, $invoice->amount);

        $this->actingAs($admin)->get(route('admin.billing.callback', ['invoice' => $invoice->id, 'Authority' => 'PLANAUTH', 'Status' => 'OK']))
            ->assertRedirect(route('admin.billing.index'))
            ->assertSessionHas('success');

        $salon->refresh();
        $this->assertSame($pro->id, (int) $salon->plan_id);
        $this->assertEqualsWithDelta($end->timestamp, $salon->subscription_ends_at->timestamp, 1);
        $this->assertSame('paid', $invoice->fresh()->status);
    }

    public function test_upgrade_is_refused_on_trial_for_a_cheaper_plan_and_when_a_change_is_scheduled(): void
    {
        $basic = $this->plan('basic', [900000]);
        $pro = $this->plan('pro', [1500000]);
        $billing = app(PlanBilling::class);

        $trial = Salon::factory()->onPlan($basic)->create(['trial_ends_at' => now()->addDays(3), 'subscription_ends_at' => now()->addDays(3)]);
        $this->assertNotNull($billing->upgradeError($trial, $pro));

        $onPro = Salon::factory()->onPlan($pro)->create(['subscription_ends_at' => now()->addDays(20)]);
        $this->assertNotNull($billing->upgradeError($onPro, $basic)); // ارزان‌تر = تنزل، از تمدید بعد

        $scheduled = Salon::factory()->onPlan($basic)->create(['subscription_ends_at' => now()->addDays(20), 'next_plan_id' => $basic->id, 'next_plan_starts_at' => now()->addDays(20)]);
        $this->assertNotNull($billing->upgradeError($scheduled, $pro));

        $ok = Salon::factory()->onPlan($basic)->create(['subscription_ends_at' => now()->addDays(20)]);
        $this->assertNull($billing->upgradeError($ok, $pro));
    }

    // ─── ثبت‌نام آزمایشی و صفحه‌ی صورتحساب ──────────────────────────────

    public function test_signup_starts_on_the_trial_plan_with_its_days_quota_and_features(): void
    {
        app(DefaultPlans::class)->sync();
        $trialPlan = Plan::trial();
        $trialPlan->update(['trial_days' => 7, 'sms_quota_per_month' => 250]);
        $trialPlan->syncFeatures(array_diff($trialPlan->featureKeys(), ['sms_reminders']));

        $this->post(route('salon-signup.store'), array_merge([
            'name' => 'سالن پلن', 'slug' => 'plan-salon', 'subscription_type' => '1m', 'specialists_count' => 3,
            'owner_name' => 'سارا', 'owner_phone' => '09125550001',
            'owner_password' => 'Str0ng!Passw0rd', 'owner_password_confirmation' => 'Str0ng!Passw0rd',
        ], $this->salonContactPayload()))->assertRedirect(route('salon-signup.verify'));
        $this->confirmSalonSignup();

        $salon = Salon::where('slug', 'plan-salon')->firstOrFail();
        $this->assertSame($trialPlan->id, (int) $salon->plan_id);
        $this->assertNull($salon->sms_quota_per_month);
        $this->assertSame(250, app(SmsQuotaService::class)->quotaFor($salon));
        $this->assertEqualsWithDelta(now()->addDays(7)->timestamp, $salon->trial_ends_at->timestamp, 5);
        $this->assertFalse($salon->hasFeature(Feature::SmsReminders));
        $this->assertTrue($salon->hasFeature(Feature::SmsCustomer));
    }

    public function test_the_billing_page_lists_the_offered_plans_with_prices_and_the_current_features(): void
    {
        app(DefaultPlans::class)->sync();
        Plan::byKey('basic')->update(['is_public' => true]);
        $salon = $this->salon();
        $salon->update(['plan_id' => Plan::byKey('basic')->id, 'max_specialists_count' => 5, 'subscription_ends_at' => now()->addDays(15)]);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get(route('admin.billing.index'))
            ->assertOk()
            ->assertSee('data-testid="current-plan"', false)
            ->assertSee('پایه')
            ->assertSee('کامل')
            ->assertSee(number_format(990000))
            ->assertSee(number_format(1500000))
            ->assertSee('ارتقای فوری')
            ->assertDontSee('کلینیک'); // غیرعمومی
    }

    public function test_the_sales_page_shows_the_same_prices_after_plans_exist_and_follows_the_public_plan(): void
    {
        $prices = (array) config('billing.subscription_prices');
        app(DefaultPlans::class)->sync();

        $page = $this->get(route('central.home'));
        $page->assertOk();
        foreach ($prices as $price) {
            $page->assertSee(to_persian_num(number_format($price)));
        }

        Plan::byKey('full')->update(['price_3m' => 4000000]);
        $this->get(route('central.home'))->assertSee(to_persian_num(number_format(4000000)));
    }
}
