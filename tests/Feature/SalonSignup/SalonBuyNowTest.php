<?php

namespace Tests\Feature\SalonSignup;

use App\Models\Invoice;
use App\Models\Salon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CompletesSalonSignup;
use Tests\Concerns\SalonContactPayload;
use Tests\TestCase;

/**
 * ⭐ «خرید مستقیم بدون دوره‌ی رایگان» (۲۰۲۶-۰۹-۲۳): کسی که از صفحه‌ی فروش «همین حالا بخرم» رو
 * می‌زنه، بعد از تایید موبایل مستقیم به همون درگاه زرین‌پال صفحه‌ی خرید پنل می‌ره، و بعد از
 * پرداخت موفق اشتراک از همون لحظه فعاله.
 */
class SalonBuyNowTest extends TestCase
{
    use CompletesSalonSignup;
    use RefreshDatabase;
    use SalonContactPayload;

    protected function setUp(): void
    {
        parent::setUp();

        config(['billing.trial_days' => 14, 'billing.subscription_prices.6m' => 7650000]);
    }

    /** فرم ثبت‌نام را می‌فرستد؛ سالن و مالک بعد از confirmSalonSignup() ساخته می‌شوند (۲۰۲۶-۱۰-۱۱). */
    private function signUpAndVerify(array $overrides = []): void
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
    }

    private function owner(): User
    {
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
        $this->signUpAndVerify();

        $response = $this->confirmSalonSignup();

        $response->assertRedirect();
        $this->assertStringContainsString('BUYNOWAUTH', $response->headers->get('Location'));
        $this->assertAuthenticatedAs($this->owner());

        $invoice = Invoice::withoutGlobalScopes()->firstOrFail();
        $this->assertSame('pending', $invoice->status);
        $this->assertSame('6m', $invoice->subscription_type);
        $this->assertSame(7650000, (int) $invoice->amount);
        $this->assertSame($this->owner()->id, $invoice->created_by);
    }

    public function test_buy_now_from_a_plan_card_buys_that_plan(): void
    {
        // بسته‌ی «و»: لندینگ چهارپلنی — «خرید فوری» روی کارت «کلینیک» همان کلینیک را می‌خرد (نه اولین پلن عمومی)
        app(\App\Services\Plans\DefaultPlans::class)->sync();
        $clinic = \App\Models\Plan::byKey('clinic');
        $card = collect($this->get(route('central.home'))->assertOk()->viewData('planCards'))->firstWhere('key', 'clinic');
        $this->assertSame(route('salon-signup.create', ['plan' => '6m', 'intent' => 'buy', 'plan_key' => 'clinic']), $card['periods']['6m']['buy_url']);
        $this->assertSame(16, $card['periods']['6m']['themes']);
        $this->get($card['periods']['6m']['buy_url'])->assertOk()->assertSee('پلن کلینیک · شش‌ماهه');

        $this->fakeGatewayRequest();
        $this->signUpAndVerify(['plan_key' => 'clinic']);
        $this->confirmSalonSignup();

        $invoice = Invoice::withoutGlobalScopes()->firstOrFail();
        $this->assertSame($clinic->id, (int) $invoice->plan_id);
        $this->assertSame('6m', $invoice->subscription_type);
        $this->assertSame((int) $clinic->price_6m, (int) $invoice->amount);
    }

    public function test_successful_payment_activates_the_plan_from_that_moment(): void
    {
        $this->fakeGatewayRequest();
        $this->signUpAndVerify();
        $this->confirmSalonSignup();
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
        $this->signUpAndVerify();

        $this->confirmSalonSignup()
            ->assertRedirect(route('admin.billing.index'))
            ->assertSessionHasErrors('error');

        $this->assertSame('failed', Invoice::withoutGlobalScopes()->firstOrFail()->status);
        $this->assertTrue(Salon::where('slug', 'niloofar')->firstOrFail()->isOnTrial());
        $this->get(route('admin.dashboard'))->assertOk();
    }

    // ─── پیامک خوش‌آمد ثبت‌نام با «خرید» (بسته‌ی «ح»، ۳، ۲۰۲۶-۱۰-۱۹) ─────────────────────────────

    private function welcomeTexts(): array
    {
        $texts = [];
        \Illuminate\Support\Facades\Notification::assertSentTo($this->owner(), \App\Notifications\Salon\SalonWelcomeNotification::class, function ($n) use (&$texts) {
            $texts[] = $n->message();

            return true;
        });

        return $texts;
    }

    public function test_buy_now_welcome_sms_waits_for_the_payment_and_names_the_bought_plan(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        \Illuminate\Support\Facades\Queue::fake([\App\Jobs\SendPendingSalonWelcomeJob::class, \App\Jobs\SendRegistrationCodeJob::class]);
        app(\App\Services\Plans\DefaultPlans::class)->sync();
        $this->fakeGatewayRequest();
        $this->signUpAndVerify(['plan_key' => 'clinic']);
        $this->confirmSalonSignup();

        // پیش از پرداخت هیچ پیامک خوش‌آمدی (پیش‌تر «دوره‌ی آزمایشی فعال است» همین‌جا می‌رفت)
        \Illuminate\Support\Facades\Notification::assertNothingSentTo($this->owner());
        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\SendPendingSalonWelcomeJob::class);

        $invoice = Invoice::withoutGlobalScopes()->firstOrFail();
        $this->get(route('admin.billing.callback', ['invoice' => $invoice->id, 'Authority' => 'BUYNOWAUTH', 'Status' => 'OK']));

        $salon = Salon::where('slug', 'niloofar')->firstOrFail();
        $texts = $this->welcomeTexts();
        $this->assertCount(1, $texts);
        $this->assertStringContainsString('پلن «کلینیک» تا '.jalali_date($salon->subscription_ends_at).' فعال است.', $texts[0]);
        $this->assertStringNotContainsString('آزمایشی', $texts[0]);
        $this->assertNull($salon->welcome_sms_pending_at);

        // مهلت پشتیبان بعداً اجرا می‌شود ولی دیگر چیزی نمی‌فرستد
        (new \App\Jobs\SendPendingSalonWelcomeJob($salon->id, $this->owner()->id, $salon->publicUrl(), route('admin.home')))->handle(app(\App\Services\SalonSignup\SalonWelcome::class));
        $this->assertCount(1, $this->welcomeTexts());
    }

    public function test_buy_now_failed_payment_sends_the_trial_welcome(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        Http::fake([
            '*request.json' => Http::response(['data' => ['code' => 100, 'authority' => 'BUYNOWAUTH']], 200),
            '*verify.json' => Http::response(['errors' => ['code' => -51]], 200),
        ]);
        $this->signUpAndVerify();
        $this->confirmSalonSignup();

        $invoice = Invoice::withoutGlobalScopes()->firstOrFail();
        $this->get(route('admin.billing.callback', ['invoice' => $invoice->id, 'Authority' => 'BUYNOWAUTH', 'Status' => 'NOK']));

        $texts = $this->welcomeTexts();
        $this->assertCount(1, $texts);
        $this->assertStringContainsString('دوره‌ی آزمایشی رایگان 14 روزه فعال است.', $texts[0]);
    }

    public function test_buy_now_gateway_unreachable_sends_the_trial_welcome_right_away(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        $this->fakeGatewayRequest(code: -9);
        $this->signUpAndVerify();
        $this->confirmSalonSignup();

        $texts = $this->welcomeTexts();
        $this->assertCount(1, $texts);
        $this->assertStringContainsString('آزمایشی', $texts[0]);
        $this->assertNull(Salon::where('slug', 'niloofar')->firstOrFail()->welcome_sms_pending_at);
    }

    public function test_buy_now_abandoned_at_the_bank_gets_the_welcome_from_the_fallback_job(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        \Illuminate\Support\Facades\Queue::fake([\App\Jobs\SendPendingSalonWelcomeJob::class, \App\Jobs\SendRegistrationCodeJob::class]);
        $this->fakeGatewayRequest();
        $this->signUpAndVerify();
        $this->confirmSalonSignup();
        \Illuminate\Support\Facades\Notification::assertNothingSentTo($this->owner());

        // job با ۳۰ دقیقه تأخیر در صف است؛ اینجا صریحاً بعد از «رها کردن» صفحه‌ی بانک اجرا می‌شود
        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\SendPendingSalonWelcomeJob::class, fn ($job) => $job->delay !== null);
        $salon = Salon::where('slug', 'niloofar')->firstOrFail();
        $this->assertNotNull($salon->welcome_sms_pending_at);
        (new \App\Jobs\SendPendingSalonWelcomeJob($salon->id, $this->owner()->id, 'http://salon.example/s/niloofar', 'http://salon.example/admin'))->handle(app(\App\Services\SalonSignup\SalonWelcome::class));

        $texts = $this->welcomeTexts();
        $this->assertCount(1, $texts);
        $this->assertStringContainsString('آزمایشی', $texts[0]);
        $this->assertStringContainsString('http://salon.example/s/niloofar', $texts[0]);
    }

    public function test_buy_now_also_works_when_the_trial_is_disabled(): void
    {
        config(['billing.trial_days' => 0]);
        $this->fakeGatewayRequest();
        $this->signUpAndVerify();

        $response = $this->confirmSalonSignup();

        $this->assertStringContainsString('BUYNOWAUTH', $response->headers->get('Location'));
    }

    public function test_default_trial_signup_is_unchanged_and_never_touches_the_gateway(): void
    {
        Http::fake();
        $this->signUpAndVerify(['intent' => null]);

        $this->confirmSalonSignup()
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
