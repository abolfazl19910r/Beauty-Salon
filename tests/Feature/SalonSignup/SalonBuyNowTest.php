<?php

namespace Tests\Feature\SalonSignup;

use App\Models\Invoice;
use App\Models\Salon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\SalonContactPayload;
use Tests\TestCase;

/**
 * ⭐ «خرید مستقیم بدون دوره‌ی رایگان» (۲۰۲۶-۰۹-۲۳): کسی که از صفحه‌ی فروش «همین حالا بخرم» رو
 * می‌زنه، بعد از تایید موبایل مستقیم به همون درگاه زرین‌پال صفحه‌ی خرید پنل می‌ره، و بعد از
 * پرداخت موفق اشتراک از همون لحظه فعاله.
 */
class SalonBuyNowTest extends TestCase
{
    use RefreshDatabase;
    use SalonContactPayload;

    protected function setUp(): void
    {
        parent::setUp();

        config(['billing.trial_days' => 14, 'billing.subscription_prices.6m' => 7650000]);
    }

    private function signUpAndVerify(array $overrides = []): User
    {
        $this->post(route('salon-signup.store'), array_merge([
            'name' => 'سالن نیلوفر',
            'slug' => 'niloofar',
            'subscription_type' => '6m',
            'intent' => 'buy',
            'owner_name' => 'مینا رضایی',
            'owner_phone' => '09123334455',
            'owner_password' => 'Str0ng!Passw0rd',
            'owner_password_confirmation' => 'Str0ng!Passw0rd',
        ], $this->salonContactPayload(), $overrides))->assertRedirect(route('salon-signup.verify'));

        return User::where('phone', '09123334455')->firstOrFail();
    }

    private function fakeGatewayRequest(int $code = 100): void
    {
        Http::fake([
            '*request.json' => Http::response(['data' => ['code' => $code, 'authority' => 'BUYNOWAUTH']], 200),
            '*verify.json' => Http::response(['data' => ['code' => 100, 'ref_id' => 'REFBUY']], 200),
        ]);
    }

    public function test_landing_offers_a_buy_now_link_for_every_plan(): void
    {
        $plans = collect($this->get(route('central.home'))->assertOk()->viewData('plans'))->keyBy('type');

        $this->assertSame(route('salon-signup.create', ['plan' => '6m', 'intent' => 'buy']), $plans['6m']['buy_url']);
        $this->get(route('central.home'))->assertSee('خرید فوری، بدون دوره‌ی رایگان');
    }

    public function test_buy_now_signup_form_shows_the_chosen_plan_but_no_plan_picker(): void
    {
        $html = $this->get(route('salon-signup.create', ['plan' => '6m', 'intent' => 'buy']))
            ->assertOk()
            ->assertSee('پلن شش‌ماهه')
            ->assertSee('۷,۶۵۰,۰۰۰ تومان', false)
            ->assertSee('ادامه، تایید موبایل و پرداخت')
            ->getContent();

        $this->assertStringContainsString('<input type="hidden" name="intent" value="buy">', $html);
        $this->assertStringNotContainsString('type="radio" name="subscription_type"', $html);
    }

    public function test_verifying_otp_with_buy_intent_goes_straight_to_the_gateway(): void
    {
        $this->fakeGatewayRequest();
        $owner = $this->signUpAndVerify();

        $response = $this->post(route('salon-signup.verify.store'), ['code' => $owner->fresh()->verification_code]);

        $response->assertRedirect();
        $this->assertStringContainsString('BUYNOWAUTH', $response->headers->get('Location'));
        $this->assertAuthenticatedAs($owner->fresh());

        $invoice = Invoice::withoutGlobalScopes()->firstOrFail();
        $this->assertSame('pending', $invoice->status);
        $this->assertSame('6m', $invoice->subscription_type);
        $this->assertSame(7650000, (int) $invoice->amount);
        $this->assertSame($owner->id, $invoice->created_by);
    }

    public function test_successful_payment_activates_the_plan_from_that_moment(): void
    {
        $this->fakeGatewayRequest();
        $owner = $this->signUpAndVerify();
        $this->post(route('salon-signup.verify.store'), ['code' => $owner->fresh()->verification_code]);
        $invoice = Invoice::withoutGlobalScopes()->firstOrFail();

        $this->get(route('admin.billing.callback', ['invoice' => $invoice->id, 'Authority' => 'BUYNOWAUTH', 'Status' => 'OK']))
            ->assertRedirect(route('admin.billing.index'))
            ->assertSessionHas('success', fn ($msg) => str_contains($msg, 'اشتراک سالن فعال شد') && str_contains($msg, '/s/niloofar'));

        $salon = Salon::where('slug', 'niloofar')->firstOrFail();
        $this->assertFalse($salon->isOnTrial());
        $this->assertEqualsWithDelta(now()->addMonths(6)->timestamp, $salon->subscription_ends_at->timestamp, 5);
        $this->assertSame('paid', $invoice->fresh()->status);
    }

    public function test_gateway_failure_leaves_the_owner_on_the_trial_with_a_retry_message(): void
    {
        $this->fakeGatewayRequest(code: -9);
        $owner = $this->signUpAndVerify();

        $this->post(route('salon-signup.verify.store'), ['code' => $owner->fresh()->verification_code])
            ->assertRedirect(route('admin.billing.index'))
            ->assertSessionHasErrors('error');

        $this->assertSame('failed', Invoice::withoutGlobalScopes()->firstOrFail()->status);
        $this->assertTrue(Salon::where('slug', 'niloofar')->firstOrFail()->isOnTrial());
        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_buy_now_also_works_when_the_trial_is_disabled(): void
    {
        config(['billing.trial_days' => 0]);
        $this->fakeGatewayRequest();
        $owner = $this->signUpAndVerify();

        $response = $this->post(route('salon-signup.verify.store'), ['code' => $owner->fresh()->verification_code]);

        $this->assertStringContainsString('BUYNOWAUTH', $response->headers->get('Location'));
    }

    public function test_default_trial_signup_is_unchanged_and_never_touches_the_gateway(): void
    {
        Http::fake();
        $owner = $this->signUpAndVerify(['intent' => null]);

        $this->post(route('salon-signup.verify.store'), ['code' => $owner->fresh()->verification_code])
            ->assertRedirect(route('admin.home'));

        Http::assertNothingSent();
        $this->assertSame(0, Invoice::withoutGlobalScopes()->count());
    }

    public function test_unknown_intent_is_rejected(): void
    {
        $this->post(route('salon-signup.store'), array_merge([
            'name' => 'x', 'slug' => 'bad-intent', 'intent' => 'hack',
            'owner_name' => 'y', 'owner_phone' => '09120000001',
            'owner_password' => 'Str0ng!Passw0rd', 'owner_password_confirmation' => 'Str0ng!Passw0rd',
        ], $this->salonContactPayload()))->assertSessionHasErrors('intent');
    }
}
