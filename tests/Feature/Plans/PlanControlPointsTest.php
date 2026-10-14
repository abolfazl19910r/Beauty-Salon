<?php

namespace Tests\Feature\Plans;

use App\Models\LoyaltyPoint;
use App\Models\NotificationSetting;
use App\Models\Plan;
use App\Models\Salon;
use App\Models\User;
use App\Models\WinbackSetting;
use App\Payments\GatewayManager;
use App\Payments\PayoutManager;
use App\Services\Marketing\WinbackService;
use App\Support\CurrentSalon;
use App\Support\Features\Feature;
use App\Support\Notifications\NotificationEvents;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** هر نقطه‌ی کنترل امکان در کد (بسته‌ی پلن‌ها): خاموش = بسته، داده پاک نمی‌شود. */
class PlanControlPointsTest extends TestCase
{
    use RefreshDatabase;

    private function currentSalonWithout(Feature ...$off): Salon
    {
        $plan = Plan::create(['key' => 'p'.uniqid(), 'name' => 'پایه', 'price_1m' => 990000, 'sms_quota_per_month' => 1000]);
        $plan->syncFeatures(array_diff(Feature::keys(), array_map(fn ($f) => $f->value, $off)));
        $salon = app(CurrentSalon::class)->get();
        $salon->update(['plan_id' => $plan->id]);

        return $salon->fresh();
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_locked_admin_pages_redirect_to_billing_and_open_pages_stay_open(): void
    {
        $this->currentSalonWithout(Feature::Winback, Feature::Loyalty, Feature::SmsTemplates);
        $admin = $this->admin();

        foreach (['admin.winback.index', 'admin.loyalty.index', 'admin.sms-templates.index'] as $route) {
            $this->actingAs($admin)->get(route($route))
                ->assertRedirect(route('admin.billing.index'))
                ->assertSessionHasErrors('error');
        }

        $this->actingAs($admin)->get(route('admin.notification-settings.index'))->assertOk();
    }

    public function test_the_same_pages_open_with_the_feature(): void
    {
        $this->currentSalonWithout();
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.winback.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.sms-templates.index'))->assertOk();
    }

    public function test_the_middleware_answers_api_requests_with_a_403_code(): void
    {
        $this->currentSalonWithout(Feature::TreatmentCourses);
        $request = \Illuminate\Http\Request::create('/api/v1/customer/courses', 'GET', server: ['HTTP_ACCEPT' => 'application/json']);

        $response = (new \App\Http\Middleware\EnsureSalonFeature)->handle($request, fn () => response('ok'), 'treatment_courses');

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame(['code' => 'feature_unavailable', 'feature' => 'treatment_courses'], array_intersect_key(json_decode($response->getContent(), true), ['code' => 1, 'feature' => 1]));

        $allowed = (new \App\Http\Middleware\EnsureSalonFeature)->handle($request, fn () => response('ok'), 'loyalty');
        $this->assertSame('ok', $allowed->getContent());
    }

    public function test_report_export_is_gated_but_the_reports_page_is_not(): void
    {
        $this->currentSalonWithout(Feature::ReportsExport);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.reports.export'), ['type' => 'excel'])->assertRedirect(route('admin.billing.index'));
        $this->actingAs($admin)->postJson(route('admin.reports.export'), ['type' => 'excel'])->assertForbidden();
    }

    public function test_online_payment_off_behaves_like_a_salon_without_gateways(): void
    {
        $salon = app(CurrentSalon::class)->get();
        $this->assertTrue($salon->acceptsOnlinePayments()); // factory درگاه پیش‌فرض دارد
        $this->assertNotEmpty(app(GatewayManager::class)->gatewaysFor($salon));

        $salon = $this->currentSalonWithout(Feature::OnlinePayment);

        $this->assertFalse($salon->acceptsOnlinePayments());
        $this->assertTrue(app(GatewayManager::class)->gatewaysFor($salon)->isEmpty());
        $this->assertTrue($salon->paymentGateways()->exists()); // ردیف درگاه پاک نشده
    }

    public function test_specialist_payouts_off_means_manual_settlement_only(): void
    {
        $salon = $this->currentSalonWithout(Feature::SpecialistPayouts);

        $this->assertNull(app(PayoutManager::class)->driverFor($salon));
        $this->assertFalse($salon->canAutoPayout());
    }

    public function test_custom_logo_off_hides_the_logo_without_deleting_it_and_refuses_a_new_upload(): void
    {
        $salon = $this->currentSalonWithout(Feature::CustomLogo);
        $salon->update(['logo_path' => 'salons/'.$salon->id.'/logo.png']);

        $this->assertNull($salon->fresh()->logoUrl());
        $this->assertNotNull($salon->fresh()->storedLogoUrl());

        $owner = User::factory()->create(['is_admin' => true]);
        $this->actingAs($owner)->put(route('admin.salon-settings.update'), [
            'name' => $salon->name,
            'logo' => \Illuminate\Http\UploadedFile::fake()->image('logo.png', 100, 100),
        ])->assertSessionHasErrors('logo');
    }

    public function test_loyalty_off_gives_no_new_points(): void
    {
        $salon = $this->currentSalonWithout(Feature::Loyalty);
        $customer = User::factory()->create(['user_type' => 'customer', 'salon_id' => $salon->id]);

        $customer->addLoyaltyPoints(50, 'تست');

        $this->assertSame(0, LoyaltyPoint::where('user_id', $customer->id)->count());
    }

    public function test_winback_runs_only_for_salons_whose_plan_has_it(): void
    {
        $salon = $this->currentSalonWithout(Feature::Winback);
        WinbackSetting::withoutGlobalScope('salon')->create(['salon_id' => $salon->id, 'enabled' => true]);

        $this->assertArrayNotHasKey($salon->id, app(WinbackService::class)->runAll());

        $salon->featureOverrides()->create(['feature' => 'winback', 'enabled' => true]);
        $this->assertArrayHasKey($salon->id, app(WinbackService::class)->runAll());
    }

    public function test_notification_settings_lock_blocked_sms_and_keep_the_salons_own_choice(): void
    {
        $salon = $this->currentSalonWithout(Feature::SmsCustomer);
        NotificationSetting::withoutGlobalScopes()->updateOrCreate(
            ['salon_id' => $salon->id, 'event_key' => NotificationEvents::BOOKING_CONFIRMED_CUSTOMER],
            ['sms_enabled' => true, 'database_enabled' => true],
        );

        $this->actingAs($this->admin())->get(route('admin.notification-settings.index'))
            ->assertOk()
            ->assertSee('data-feature-locked="sms_customer"', false)
            ->assertSee('name="sms[booking__confirmed__customer]" value="1"', false);

        $this->assertSame(Feature::SmsStaff, NotificationEvents::smsFeature(NotificationEvents::BOOKING_CREATED_SPECIALIST));
        $this->assertSame(Feature::SmsCustomer, NotificationEvents::smsFeature(NotificationEvents::BOOKING_COMPLETED_REVIEW_REQUEST));
        $this->assertNull(NotificationEvents::smsFeature(NotificationEvents::PAYMENT_ATTENTION_ADMIN));
        $this->assertNull(NotificationEvents::smsFeature(NotificationEvents::SMS_QUOTA_EXHAUSTED_ADMIN));
    }

    public function test_the_apps_get_the_enabled_feature_list(): void
    {
        $salon = $this->currentSalonWithout(Feature::Loyalty);

        $account = \App\Services\Api\ApiAccountPresenter::present(User::factory()->create(['user_type' => 'customer', 'salon_id' => $salon->id]), 'customer', $salon);
        $this->assertNotContains('loyalty', $account['salon']['features']);
        $this->assertContains('sms_customer', $account['salon']['features']);

        $this->assertNotContains('loyalty', \App\Services\Api\CustomerPresenter::salonDetail($salon)['features']);
    }
}
