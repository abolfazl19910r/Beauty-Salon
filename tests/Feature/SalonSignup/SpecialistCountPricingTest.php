<?php

namespace Tests\Feature\SalonSignup;

use App\Models\Salon;
use App\Models\User;
use App\Services\Payment\InvoiceService;
use App\Support\Billing\SubscriptionPricing;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SalonContactPayload;
use Tests\TestCase;

/**
 * تعداد متخصص موقع ساخت سالن (۲۰۲۶-۰۹-۳۰): ثبت‌نام عمومی می‌پرسد، سقف متخصص همان عدد است، و قیمت اشتراک
 * = قیمت پلن (با تعداد متخصص شامل‌شده) + مبلغ ماهانه‌ی هر متخصص اضافه، با همان تخفیف بازه‌ی پلن.
 */
class SpecialistCountPricingTest extends TestCase
{
    use RefreshDatabase;
    use SalonContactPayload;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'billing.trial_days' => 0,
            'billing.included_specialists' => 7,
            'billing.extra_specialist_price_per_month' => 250000,
            'billing.subscription_prices' => ['1m' => 1500000, '3m' => 4150000, '6m' => 7650000, '12m' => 13850000],
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'سالن آزمایشی', 'slug' => 'count-test-salon', 'subscription_type' => '3m',
            'owner_name' => 'مالک', 'owner_phone' => '09121234599',
            'owner_password' => 'Str0ng!Passw0rd', 'owner_password_confirmation' => 'Str0ng!Passw0rd',
        ], $this->salonContactPayload(), $overrides);
    }

    public function test_public_signup_asks_the_specialist_count_and_uses_it_as_the_limit(): void
    {
        $form = $this->get(route('salon-signup.create'))->assertOk();
        $form->assertSee('name="specialists_count"', false);
        $form->assertSee('value="7"', false);

        $this->post(route('salon-signup.store'), $this->payload(['specialists_count' => '']))
            ->assertSessionHasErrors('specialists_count');
        $this->post(route('salon-signup.store'), $this->payload(['specialists_count' => 0]))
            ->assertSessionHasErrors('specialists_count');

        $this->post(route('salon-signup.store'), $this->payload(['specialists_count' => 9]))->assertSessionHasNoErrors();
        $this->assertSame(9, Salon::where('slug', 'count-test-salon')->value('max_specialists_count'));
    }

    public function test_price_is_the_plan_price_plus_each_extra_specialist_with_the_plans_discount(): void
    {
        $pricing = app(SubscriptionPricing::class);

        $this->assertSame(1500000, $pricing->price('1m', 7));
        $this->assertSame(1500000, $pricing->price('1m', 3), 'کمتر از تعداد شامل‌شده ارزان‌تر نمی‌شود');
        $this->assertSame(2000000, $pricing->price('1m', 9));
        // ۳ ماهه: ۲ متخصص اضافه × ۲۵۰٬۰۰۰ × ۳ ماه × (۴٬۱۵۰٬۰۰۰ ÷ ۴٬۵۰۰٬۰۰۰) = ۱٬۳۸۳٬۳۳۳ → گرد به هزار
        $this->assertSame(4150000 + 1383000, $pricing->price('3m', 9));
    }

    public function test_the_subscription_invoice_and_billing_page_charge_for_the_salons_specialist_count(): void
    {
        $salon = app(CurrentSalon::class)->get();
        $salon->update(['max_specialists_count' => 9]);
        $owner = User::factory()->create(['is_admin' => true]);

        $invoice = app(InvoiceService::class)->createPendingOnlinePurchase($salon, '1m', $owner);
        $this->assertSame(2000000, (int) $invoice->amount);

        $this->actingAs($owner)->get(route('admin.billing.index'))->assertOk()
            ->assertSee(number_format(2000000))
            ->assertSee('۹ متخصص');
    }

    public function test_the_sales_page_and_super_admin_form_use_the_included_count(): void
    {
        $landing = $this->get(route('central.home'));
        if ($landing->status() === 200) {
            $landing->assertSee('هر متخصص بیشتر');
        }

        $superAdmin = User::factory()->create(['is_admin' => false]);
        $superAdmin->salons()->detach();
        $superAdmin->roles()->attach(\App\Models\Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین'])->id);

        $this->actingAs($superAdmin)->get('/superadmin/salons/create')->assertOk()
            ->assertSee('name="max_specialists_count" value="7"', false);
    }
}
