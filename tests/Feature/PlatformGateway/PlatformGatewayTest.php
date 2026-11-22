<?php

namespace Tests\Feature\PlatformGateway;

use App\Models\PaymentTransaction;
use App\Models\PlatformGatewayRequest;
use App\Models\PlatformSettlement;
use App\Models\Role;
use App\Models\Salon;
use App\Models\SalonPaymentGateway;
use App\Models\User;
use App\Payments\GatewayManager;
use App\Payments\GatewayStartRequest;
use App\Payments\PlatformGateway;
use App\Services\Payment\PlatformGateway\PlatformGatewayLedger;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * درگاه پرداخت پلتفرم برای سالن‌ها (۲۰۲۶-۱۱-۲۲): درخواست مالک، تأیید/رد ابرمدیر با درصد کارمزد، عکس‌برداری درصد روی هر تراکنش،
 * حساب جدای هر سالن (کل، سهم پلتفرم، سهم سالن، واریزشده، مانده) و تسویه (ساخت، واریز، لغو، خودکار، CSV).
 */
class PlatformGatewayTest extends TestCase
{
    use RefreshDatabase;

    private const IBAN_DIGITS = '820540102680020817909002';

    private Salon $salon;

    private User $owner;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->salon = app(CurrentSalon::class)->get();
        $this->owner = User::factory()->create(['is_admin' => true]); // اولین ادمین = owner
        $this->superAdmin = User::factory()->create(['is_admin' => true]);
        $this->superAdmin->roles()->attach(Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']));
        config(['payments.platform_gateway.enabled' => true, 'services.zarinpal.sandbox' => true]);
    }

    private function submit(array $overrides = [])
    {
        return $this->actingAs($this->owner)->post(route('admin.payment-gateways.platform.store'), $overrides + [
            'payout_method' => 'iban', 'destination' => 'IR۸۲ ۰۵۴۰ ۱۰۲۶ ۸۰۰۲ ۰۸۱۷ ۹۰۹۰ ۰۲', 'account_holder' => 'سارا محمدی', 'accept_fee' => '1',
        ]);
    }

    private function approved(Salon $salon, float $fee = 10): PlatformGatewayRequest
    {
        $req = PlatformGatewayRequest::create([
            'salon_id' => $salon->id, 'status' => 'pending', 'payout_method' => 'iban', 'payout_iban' => 'IR'.self::IBAN_DIGITS, 'account_holder' => 'مالک '.$salon->id,
        ]);
        app(\App\Services\Payment\PlatformGateway\PlatformGatewayService::class)->approve($req, $this->superAdmin, $fee);

        return $req->fresh();
    }

    private function tx(Salon $salon, int $amountRial, string $status = 'paid', string $driver = PlatformGateway::DRIVER): PaymentTransaction
    {
        return PaymentTransaction::create([
            'salon_id' => $salon->id, 'driver' => $driver, 'purpose' => 'booking', 'amount_rial' => $amountRial,
            'status' => $status, 'callback_url' => 'https://example.test/cb',
        ]);
    }

    public function test_owner_does_not_see_or_reach_the_request_while_the_feature_is_off(): void
    {
        config(['payments.platform_gateway.enabled' => false]);

        $this->actingAs($this->owner)->get(route('admin.payment-gateways.index'))->assertOk()->assertDontSee('data-platform-gateway', false);
        $this->submit()->assertNotFound();
        $this->assertSame(0, PlatformGatewayRequest::count());
    }

    public function test_owner_requests_with_a_persian_iban_once_and_numbers_are_stored_encrypted(): void
    {
        $this->actingAs($this->owner)->get(route('admin.payment-gateways.index'))->assertOk()->assertSee('data-platform-gateway-form', false);

        $this->submit()->assertSessionHasNoErrors()->assertRedirect();
        $req = PlatformGatewayRequest::sole();
        $this->assertSame('pending', $req->status);
        $this->assertSame('IR'.self::IBAN_DIGITS, $req->payout_iban);
        $this->assertStringNotContainsString(self::IBAN_DIGITS, (string) DB::table('platform_gateway_requests')->value('payout_iban'));

        $this->submit()->assertSessionHas('error');
        $this->assertSame(1, PlatformGatewayRequest::count());
        $this->actingAs($this->owner)->get(route('admin.payment-gateways.index'))->assertSee('data-platform-gateway-status="pending"', false)
            ->assertSee('…9002')->assertDontSee(self::IBAN_DIGITS);
    }

    public function test_invalid_destinations_and_missing_consent_are_rejected_and_staff_cannot_request(): void
    {
        $this->submit(['destination' => 'IR820540102680020817909003'])->assertSessionHasErrors('destination');
        $this->submit(['payout_method' => 'card', 'destination' => '6037991234567890'])->assertSessionHasErrors('destination');
        $this->submit(['payout_method' => 'account', 'destination' => '0123456789'])->assertSessionHasErrors('payout_bank');
        $this->submit(['accept_fee' => null])->assertSessionHasErrors('accept_fee');
        $this->assertSame(0, PlatformGatewayRequest::count());

        $staff = User::factory()->create(['is_admin' => true]);
        $this->salon->admins()->syncWithoutDetaching([$staff->id => ['role' => 'staff']]);
        $this->actingAs($staff)->post(route('admin.payment-gateways.platform.store'), ['payout_method' => 'iban'])->assertForbidden();
    }

    public function test_super_admin_approves_with_a_persian_fee_and_the_platform_gateway_becomes_payable(): void
    {
        $this->submit();
        $req = PlatformGatewayRequest::sole();

        $this->actingAs($this->owner)->get(route('superadmin.platform-gateway.index'))->assertForbidden();
        $this->actingAs($this->superAdmin)->get(route('superadmin.platform-gateway.index'))->assertOk()->assertSee($this->salon->name);
        $this->actingAs($this->superAdmin)->get(route('superadmin.platform-gateway.requests.show', $req))->assertOk()->assertSee('IR'.self::IBAN_DIGITS);

        $this->actingAs($this->superAdmin)->post(route('superadmin.platform-gateway.requests.approve', $req), ['fee_percent' => '۳۱'])->assertSessionHasErrors('fee_percent');
        $this->actingAs($this->superAdmin)->post(route('superadmin.platform-gateway.requests.approve', $req), ['fee_percent' => '۱۰٫۵'])->assertRedirect();

        $req->refresh();
        $this->assertSame('approved', $req->status);
        $this->assertSame('10.50', (string) $req->fee_percent);
        $row = $this->salon->paymentGateways()->where('driver', PlatformGateway::DRIVER)->sole();
        $this->assertTrue($row->is_active);
        $this->assertSame(0, $row->priority);

        // پرداخت مشتری: درگاه پلتفرم دیده می‌شود؛ فهرست درگاه‌های مالک (enabledDriver) و تسویه‌ی متخصص آن را نمی‌بینند
        $this->assertTrue(app(GatewayManager::class)->gatewaysFor($this->salon->fresh())->contains('id', $row->id));
        $this->salon->paymentGateways()->where('driver', '!=', PlatformGateway::DRIVER)->delete();
        $this->assertTrue($this->salon->fresh()->acceptsOnlinePayments(), 'سالنی که فقط درگاه پلتفرم دارد پرداخت آنلاین می‌گیرد');
        $this->assertFalse($this->salon->paymentGateways()->enabledDriver()->whereKey($row->id)->exists());
        $this->assertNull(app(\App\Payments\PayoutManager::class)->gatewayFor($this->salon));

        config(['payments.platform_gateway.enabled' => false]);
        $this->assertFalse(app(GatewayManager::class)->gatewaysFor($this->salon->fresh())->contains('id', $row->id));
    }

    public function test_rejection_keeps_the_gateway_off_and_lets_the_owner_ask_again(): void
    {
        $this->submit();
        $req = PlatformGatewayRequest::sole();

        $this->actingAs($this->superAdmin)->post(route('superadmin.platform-gateway.requests.reject', $req), [])->assertSessionHasErrors('reason');
        $this->actingAs($this->superAdmin)->post(route('superadmin.platform-gateway.requests.reject', $req), ['reason' => 'نام صاحب حساب با مالک نمی‌خواند'])->assertRedirect();

        $this->assertSame('rejected', $req->fresh()->status);
        $this->assertSame(0, SalonPaymentGateway::query()->where('driver', PlatformGateway::DRIVER)->count());
        $this->actingAs($this->owner)->get(route('admin.payment-gateways.index'))->assertSee('نام صاحب حساب با مالک نمی‌خواند')->assertSee('data-platform-gateway-form', false);
        $this->submit()->assertSessionHasNoErrors();
        $this->assertSame(2, PlatformGatewayRequest::count());
    }

    public function test_each_transaction_keeps_the_fee_it_was_made_with(): void
    {
        $req = $this->approved($this->salon, 10.5);

        $first = $this->tx($this->salon, 1_000_000);
        $this->assertSame('10.50', (string) $first->platform_fee_percent);
        $this->assertSame(105_000, $first->platform_share_rial);
        $this->assertSame(895_000, $first->salonShareRial());

        $this->actingAs($this->superAdmin)->put(route('superadmin.platform-gateway.fee', $this->salon->id), ['fee_percent' => '9'])->assertRedirect();
        $this->assertSame('9.00', (string) $req->fresh()->fee_percent);

        $second = $this->tx($this->salon, 333_333);
        $this->assertSame(30_000, $second->platform_share_rial); // ۹٪ از ۳۳۳٬۳۳۳ = ۲۹٬۹۹۹٫۹۷ ← ۳۰٬۰۰۰
        $this->assertSame(105_000, $first->fresh()->platform_share_rial);

        // در جایگزینی خودکار با درگاه خود سالن پرداخت شد ← سهمی برای پلتفرم نیست
        $second->update(['driver' => 'zarinpal']);
        $this->assertNull($second->fresh()->platform_share_rial);
        $this->assertNull($second->fresh()->platform_fee_percent);
    }

    public function test_the_ledger_is_separate_per_salon_and_counts_only_money_that_belongs_to_the_salon(): void
    {
        $other = Salon::factory()->create();
        $this->approved($this->salon, 10);
        $this->approved($other, 5);

        $this->tx($this->salon, 1_000_000);
        $this->tx($this->salon, 500_000, 'refunded');   // به کیف پول مشتری همان سالن ← مال سالن
        $this->tx($this->salon, 700_000, 'failed');
        $this->tx($this->salon, 900_000, 'reversed');   // به کارت مشتری برگشت
        $this->tx($this->salon, 400_000, 'paid', 'zarinpal'); // مرچنت خود سالن
        $this->tx($other, 2_000_000);

        $ledger = app(PlatformGatewayLedger::class);
        $mine = $ledger->summary($this->salon->id);
        $this->assertSame(['gross' => 1_500_000, 'platform' => 150_000, 'salon' => 1_350_000, 'settled' => 0, 'pending' => 0, 'outstanding' => 1_350_000, 'count' => 2, 'unsettled_count' => 2], $mine);
        $theirs = $ledger->summary($other->id);
        $this->assertSame([2_000_000, 100_000, 1_900_000], [$theirs['gross'], $theirs['platform'], $theirs['salon']]);

        $this->actingAs($this->superAdmin)->get(route('superadmin.platform-gateway.salon', $this->salon->id))->assertOk()
            ->assertSeeInOrder(['data-sum-gross', '۱۵۰,۰۰۰', 'data-sum-platform', '۱۵,۰۰۰', 'data-sum-salon', '۱۳۵,۰۰۰'], false);
    }

    public function test_settlement_lifecycle_create_pay_cancel_and_csv(): void
    {
        $other = Salon::factory()->create();
        $this->approved($this->salon, 10);
        $this->approved($other, 10);
        $a = $this->tx($this->salon, 1_000_000);
        $b = $this->tx($this->salon, 2_000_000);
        $theirs = $this->tx($other, 3_000_000);

        $this->actingAs($this->superAdmin)->post(route('superadmin.platform-gateway.settlements.store', $this->salon->id))->assertSessionHas('success');
        $s = PlatformSettlement::sole();
        $this->assertSame([2, 3_000_000, 300_000, 2_700_000], [$s->transactions_count, $s->gross_rial, $s->platform_share_rial, $s->salon_share_rial]);
        $this->assertSame('IR'.self::IBAN_DIGITS, $s->payout_destination);
        $this->assertSame($s->id, $a->fresh()->platform_settlement_id);
        $this->assertNull($theirs->fresh()->platform_settlement_id, 'تسویه‌ی یک سالن تراکنش سالن دیگر را نمی‌گیرد');

        // چیزی برای تسویه نمانده
        $this->actingAs($this->superAdmin)->post(route('superadmin.platform-gateway.settlements.store', $this->salon->id))->assertSessionHas('error');

        $csv = $this->actingAs($this->superAdmin)->get(route('superadmin.platform-gateway.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString('IR'.self::IBAN_DIGITS, $csv);
        $this->assertStringContainsString(',270000,2700000,', $csv);

        $this->actingAs($this->superAdmin)->post(route('superadmin.platform-gateway.settlements.cancel', $s))->assertSessionHas('success');
        $this->assertSame('cancelled', $s->fresh()->status);
        $this->assertNull($b->fresh()->platform_settlement_id);

        $this->actingAs($this->superAdmin)->post(route('superadmin.platform-gateway.settlements.store', $this->salon->id));
        $s2 = PlatformSettlement::query()->where('status', 'pending')->sole();
        $this->actingAs($this->superAdmin)->post(route('superadmin.platform-gateway.settlements.paid', $s2), [])->assertSessionHasErrors('bank_reference');
        $this->actingAs($this->superAdmin)->post(route('superadmin.platform-gateway.settlements.paid', $s2), ['bank_reference' => '140508011234'])->assertSessionHas('success');

        $sum = app(PlatformGatewayLedger::class)->summary($this->salon->id);
        $this->assertSame([2_700_000, 0, 0], [$sum['settled'], $sum['pending'], $sum['outstanding']]);
        $this->actingAs($this->owner)->get(route('admin.payment-gateways.index'))->assertSee('140508011234')->assertSee('data-platform-gateway-summary', false);
    }

    public function test_settle_all_and_the_weekly_command_respect_the_minimum_and_skip_recent_payments(): void
    {
        $small = Salon::factory()->create();
        $this->approved($this->salon, 10);
        $this->approved($small, 10);
        $old = $this->tx($this->salon, 5_000_000);
        PaymentTransaction::whereKey($old->id)->toBase()->update(['updated_at' => now()->subDays(2)]);
        $this->tx($this->salon, 1_000_000); // امروز ← در اجرای خودکار کنار می‌ماند
        $tiny = $this->tx($small, 50_000);
        PaymentTransaction::whereKey($tiny->id)->toBase()->update(['updated_at' => now()->subDays(2)]);

        $this->artisan('platform-gateway:settle', ['--min-toman' => 100000])->assertSuccessful();
        $s = PlatformSettlement::sole();
        $this->assertTrue($s->automatic);
        $this->assertSame([1, 4_500_000], [$s->transactions_count, $s->salon_share_rial]);

        $this->actingAs($this->superAdmin)->post(route('superadmin.platform-gateway.settle-all'))->assertSessionHas('success');
        $this->assertSame(3, PlatformSettlement::count());
    }

    public function test_auto_settlement_runs_every_24_hours_only_when_switched_on(): void
    {
        $find = fn () => collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->first(fn ($e) => str_contains((string) $e->command, 'platform-gateway:settle'));

        config(['payments.platform_gateway.auto_settle' => true]);
        $this->artisan('schedule:list')->assertSuccessful();
        $this->assertSame('20 9 * * *', $find()?->expression);
    }

    public function test_revoked_salon_stops_taking_payments_but_its_balance_can_still_be_settled(): void
    {
        $this->approved($this->salon, 10);
        $this->tx($this->salon, 1_000_000);

        $this->actingAs($this->superAdmin)->post(route('superadmin.platform-gateway.revoke', $this->salon->id), ['reason' => 'پایان همکاری'])->assertSessionHas('success');
        $this->assertFalse($this->salon->paymentGateways()->where('driver', PlatformGateway::DRIVER)->sole()->is_active);
        $this->assertFalse(app(GatewayManager::class)->gatewaysFor($this->salon->fresh())->contains('driver', PlatformGateway::DRIVER));

        $this->actingAs($this->superAdmin)->post(route('superadmin.platform-gateway.settlements.store', $this->salon->id))->assertSessionHas('success');
        $this->assertSame(900_000, PlatformSettlement::sole()->salon_share_rial);
    }

    public function test_the_platform_driver_uses_the_platform_merchant_and_never_an_unknown_one(): void
    {
        $this->approved($this->salon, 10);
        $row = $this->salon->paymentGateways()->where('driver', PlatformGateway::DRIVER)->sole();

        Http::fake(['sandbox.zarinpal.com/*' => Http::response(['data' => ['code' => 100, 'authority' => 'A0000000000000000000000000000012345'], 'errors' => []])]);
        $result = app(GatewayManager::class)->driver($row)->start(new GatewayStartRequest(100_000, 'https://example.test/cb', 'تست'));
        $this->assertTrue($result->success);
        Http::assertSent(fn ($r) => $r['merchant_id'] === \App\Support\PlatformZarinpal::merchantId() && str_contains($r->url(), 'sandbox.zarinpal.com'));

        // درگاه واقعی بی مرچنت پلتفرم ← بی تماس رد می‌شود (درگاه بعدی سالن امتحان می‌شود)
        config(['services.zarinpal.sandbox' => false, 'services.zarinpal.merchant_id' => '']);
        Http::fake();
        $result = app(GatewayManager::class)->driver($row)->start(new GatewayStartRequest(100_000, 'https://example.test/cb', 'تست'));
        $this->assertFalse($result->success);
        $this->assertTrue($result->retryable);
        Http::assertNothingSent();
    }
}
