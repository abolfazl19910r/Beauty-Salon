<?php

namespace Tests\Feature\Payment;

use App\Models\BeautyService;
use App\Models\Role;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\SpecialistSchedule;
use App\Models\User;
use App\Services\PaymentService;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\SalonContactPayload;
use Tests\TestCase;

/**
 * ⭐ کد پذیرنده‌ی زرین‌پال هر سالن (۲۰۲۶-۰۹-۲۴): کجا وارد می‌شه (ثبت‌نام، صفحه‌ی «اطلاعات سالن»
 * مالک، فرم سوپرادمین)، و این‌که بدونش هیچ پرداخت آنلاینی برای مشتری‌ها ممکن نیست.
 */
class SalonMerchantIdTest extends TestCase
{
    use RefreshDatabase;
    use SalonContactPayload;

    private const MERCHANT = '1a2b3c4d-5e6f-4a1b-9c8d-7e6f5a4b3c2d';

    private Salon $salon;

    protected function setUp(): void
    {
        parent::setUp();

        $this->salon = app(CurrentSalon::class)->get();
    }

    private function withoutMerchant(): void
    {
        $this->salon->update(['zarinpal_merchant_id' => null]);
        // PaymentService مرچنت رو در constructor می‌خونه
        $this->app->forgetInstance(PaymentService::class);
    }

    public function test_signup_accepts_an_optional_merchant_id_and_explains_why_it_matters(): void
    {
        $this->get(route('salon-signup.create'))
            ->assertOk()
            ->assertSee('درگاه پرداخت سالن (زرین‌پال)')
            ->assertSee('بدون این کد هیچ پرداخت آنلاینی برای مشتری‌های سالن شما ممکن نیست');

        $base = [
            'name' => 'سالن گلسار', 'owner_name' => 'مینا', 'owner_password' => 'Str0ng!Passw0rd',
            'owner_password_confirmation' => 'Str0ng!Passw0rd',
        ] + $this->salonContactPayload();

        $this->post(route('salon-signup.store'), $base + ['slug' => 'golsar', 'owner_phone' => '09121110001', 'zarinpal_merchant_id' => strtoupper(self::MERCHANT)])
            ->assertRedirect(route('salon-signup.verify'));
        $this->assertSame(self::MERCHANT, Salon::where('slug', 'golsar')->value('zarinpal_merchant_id'));

        $this->post(route('salon-signup.store'), $base + ['slug' => 'golsar-2', 'owner_phone' => '09121110002'])
            ->assertRedirect(route('salon-signup.verify'));
        $this->assertNull(Salon::where('slug', 'golsar-2')->value('zarinpal_merchant_id'));
    }

    public function test_malformed_merchant_id_is_rejected(): void
    {
        $this->post(route('salon-signup.store'), ['zarinpal_merchant_id' => 'not-a-merchant'])
            ->assertSessionHasErrors('zarinpal_merchant_id');
    }

    /**
     * ⭐ مرحله‌ی ۱ چند درگاه (۲۰۲۶-۰۹-۲۵): فیلد تکی کد پذیرنده از «اطلاعات سالن» حذف شد؛ مالک از صفحه‌ی
     * «درگاه‌های پرداخت» زرین‌پال رو اضافه/حذف می‌کنه و ستون salons.zarinpal_merchant_id هم‌گام می‌مونه.
     */
    public function test_owner_sets_and_clears_the_merchant_id_from_the_payment_gateways_page(): void
    {
        $owner = User::factory()->create(['is_admin' => true]); // اولین ادمین = owner
        $this->withoutMerchant();

        $this->actingAs($owner)->get(route('admin.salon-settings.edit'))
            ->assertOk()->assertSee('پرداخت آنلاین سالن غیرفعال است')
            ->assertSee(route('admin.payment-gateways.index'), false)
            ->assertDontSee('name="zarinpal_merchant_id"', false);

        $this->actingAs($owner)->put(route('admin.salon-settings.update'), [
            'name' => $this->salon->name, 'zarinpal_merchant_id' => self::MERCHANT,
        ])->assertSessionHasNoErrors();
        $this->assertFalse($this->salon->fresh()->acceptsOnlinePayments(), 'فیلد قدیمی دیگه اثری نداره');

        $this->actingAs($owner)->post(route('admin.payment-gateways.store'), [
            'driver' => 'zarinpal', 'credentials' => ['merchant_id' => strtoupper(self::MERCHANT)], 'is_active' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertTrue($this->salon->fresh()->acceptsOnlinePayments());
        $this->assertSame(self::MERCHANT, $this->salon->fresh()->zarinpal_merchant_id);

        $gateway = $this->salon->paymentGateways()->sole();
        $this->actingAs($owner)->delete(route('admin.payment-gateways.destroy', $gateway->id));
        $this->assertFalse($this->salon->fresh()->acceptsOnlinePayments());
        $this->assertNull($this->salon->fresh()->zarinpal_merchant_id);
    }

    public function test_super_admin_merchant_field_is_validated_and_normalised(): void
    {
        $role = Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']);
        $superAdmin = User::factory()->create(['is_admin' => true]);
        $superAdmin->roles()->attach($role);
        $other = Salon::factory()->create();
        $base = ['name' => $other->name, 'max_specialists_count' => $other->max_specialists_count];

        $this->actingAs($superAdmin)->put("/superadmin/salons/{$other->id}", $base + ['zarinpal_merchant_id' => 'x'])
            ->assertSessionHasErrors('zarinpal_merchant_id');
        $this->actingAs($superAdmin)->put("/superadmin/salons/{$other->id}", $base + ['zarinpal_merchant_id' => ' '.strtoupper(self::MERCHANT).' ']);
        $this->assertSame(self::MERCHANT, $other->fresh()->zarinpal_merchant_id);
    }

    public function test_dashboard_warns_when_the_salon_has_no_merchant(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertDontSee('درگاه پرداخت سالن شما هنوز تنظیم نشده است');

        $this->withoutMerchant();
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertSee('درگاه پرداخت سالن شما هنوز تنظیم نشده است');
    }

    public function test_booking_that_needs_prepayment_is_blocked_without_a_merchant(): void
    {
        $this->withoutMerchant();
        \App\Models\WalletSetting::get()->update(['prepayment_percentage' => 20, 'minimum_prepayment_amount' => 0]);
        $user = User::factory()->create();
        $service = BeautyService::factory()->create(['price' => 200000, 'duration' => 30]);
        $specialist = Specialist::factory()->create();
        $target = now()->addDay()->setTime(10, 0);
        SpecialistSchedule::factory()->create([
            'specialist_id' => $specialist->id, 'day_of_week' => $target->dayOfWeek,
            'start_time' => '08:00', 'end_time' => '20:00', 'is_active' => true,
        ]);

        $this->actingAs($user)->get(route('bookings.create'))
            ->assertOk()->assertSee('پرداخت آنلاین این سالن هنوز فعال نشده است');

        $this->actingAs($user)->from(route('bookings.create'))->post(route('bookings.confirm'), [
            'service_id' => $service->id, 'specialist_id' => $specialist->id, 'booking_time' => $target->format('Y-m-d H:i:s'),
        ])->assertRedirect(route('bookings.create'))->assertSessionHas('error');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_wallet_charge_never_goes_to_the_platform_merchant(): void
    {
        $this->withoutMerchant();
        Http::fake();

        $result = app(PaymentService::class)->createWalletChargePayment(User::factory()->create(), 50000);

        $this->assertFalse($result['success']);
        $this->assertSame('merchant_missing', $result['reason']);
        Http::assertNothingSent();
    }

    public function test_service_without_prepayment_can_still_be_booked_without_a_merchant(): void
    {
        $this->withoutMerchant();
        \App\Models\WalletSetting::get()->update(['prepayment_percentage' => 0, 'minimum_prepayment_amount' => 0]);
        $user = User::factory()->create();
        $service = BeautyService::factory()->create(['price' => 200000, 'duration' => 30]);
        $specialist = Specialist::factory()->create();
        $target = now()->addDay()->setTime(10, 0);
        SpecialistSchedule::factory()->create([
            'specialist_id' => $specialist->id, 'day_of_week' => $target->dayOfWeek,
            'start_time' => '08:00', 'end_time' => '20:00', 'is_active' => true,
        ]);

        $this->actingAs($user)->post(route('bookings.confirm'), [
            'service_id' => $service->id, 'specialist_id' => $specialist->id, 'booking_time' => $target->format('Y-m-d H:i:s'),
        ])->assertOk();
    }
}
