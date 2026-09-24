<?php

namespace Tests\Feature\Payment;

use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Payments\Drivers\ZibalPayoutDriver;
use App\Payments\PayoutManager;
use App\Payments\PayoutRequest;
use App\Services\Payment\SalonPayoutService;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * ⭐ تسویه‌ی خودکار متخصص از کیف پول زیبال سالن (مرحله‌ی ۳ چند درگاه، ۲۰۲۶-۰۹-۲۵).
 */
class ZibalPayoutTest extends TestCase
{
    use RefreshDatabase;

    private Salon $salon;

    protected function setUp(): void
    {
        parent::setUp();
        $this->salon = app(CurrentSalon::class)->get();
    }

    private function zibal(array $credentials = []): \App\Models\SalonPaymentGateway
    {
        return $this->salon->paymentGateways()->create([
            'driver' => 'zibal', 'priority' => 5, 'is_active' => true,
            'credentials' => $credentials + ['merchant' => 'zibal'],
        ]);
    }

    public function test_driver_posts_the_documented_checkout_request_in_rial(): void
    {
        Http::fake(['api.zibal.ir/v1/wallet/checkout' => Http::response(['result' => 1, 'message' => 'موفق', 'data' => ['id' => 'xfg99754ae7abb06d63f1d60', 'amount' => 1500000]])]);

        $result = (new ZibalPayoutDriver(['payout_access_token' => 'zb-token', 'payout_wallet_id' => '10101']))
            ->payout(new PayoutRequest(1500000, 'IR060180000000000000020600', 'تسویه حساب متخصص', '7'));

        $this->assertTrue($result->success);
        $this->assertSame('xfg99754ae7abb06d63f1d60', $result->referenceCode);
        Http::assertSent(fn (Request $r) => $r->header('Authorization') === ['Bearer zb-token']
            && $r['amount'] === 1500000 && $r['id'] === 10101
            && $r['bankAccount'] === 'IR060180000000000000020600'
            && ! isset($r['checkoutDelay']));
    }

    public function test_driver_reports_failures_and_auth_errors(): void
    {
        Http::fake(['api.zibal.ir/*' => Http::sequence()
            ->push(['result' => 6, 'message' => 'موجودی کیف پول کافی نیست'])
            ->push(['message' => 'Unauthorized'], 401)]);
        $driver = new ZibalPayoutDriver(['payout_access_token' => 't', 'payout_wallet_id' => '1']);
        $request = new PayoutRequest(10000, 'IR060180000000000000020600', 'x', '1');

        $first = $driver->payout($request);
        $this->assertFalse($first->success);
        $this->assertSame('موجودی کیف پول کافی نیست', $first->message);
        $this->assertStringContainsString('توکن دسترسی زیبال', $driver->payout($request)->message);
    }

    public function test_salon_withdrawal_is_paid_from_zibal_when_zarinpal_has_no_payout_token(): void
    {
        $this->assertNull($this->salon->zarinpal_payout_api_key);
        $this->zibal(['payout_access_token' => 'salon-zibal-token', 'payout_wallet_id' => '2020']);
        $this->assertTrue($this->salon->fresh()->canAutoPayout());
        Http::fake(['api.zibal.ir/v1/wallet/checkout' => Http::response(['result' => 1, 'data' => ['id' => 'ZB-1']])]);
        $withdrawal = WithdrawalRequest::factory()->create([
            'status' => 'processing', 'specialist_id' => Specialist::factory()->create()->id,
            'amount' => 250000, 'net_amount' => 250000, 'iban' => 'IR060180000000000000020600',
        ]);

        $result = app(SalonPayoutService::class)->payout($withdrawal);

        $this->assertTrue($result['success']);
        $this->assertSame('ZB-1', $result['reference_code']);
        Http::assertSent(fn (Request $r) => $r['amount'] === 2500000 && $r['id'] === 2020);
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), 'zarinpal'));
    }

    public function test_incomplete_zibal_payout_details_do_not_enable_auto_payout(): void
    {
        $gateway = $this->zibal(['payout_access_token' => 'only-token']);

        $this->assertFalse(app(PayoutManager::class)->isConfigured($gateway));
        $this->assertFalse($this->salon->fresh()->canAutoPayout());
    }

    public function test_owner_enters_zibal_payout_details_on_the_gateways_page(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $gateway = $this->zibal();

        $this->actingAs($owner)->put(route('admin.payment-gateways.update', $gateway->id), [
            'credentials' => ['merchant' => 'zibal', 'payout_access_token' => 'panel-token', 'payout_wallet_id' => '۳۰۳۰'],
            'is_active' => '1',
        ])->assertSessionHasNoErrors(); // ارقام فارسی شناسه‌ی کیف پول به لاتین تبدیل می‌شن

        $this->assertSame('panel-token', $gateway->fresh()->credentials['payout_access_token']);
        $this->assertTrue($this->salon->fresh()->canAutoPayout());
        $this->actingAs($owner)->get(route('admin.payment-gateways.index'))->assertDontSee('panel-token')->assertSee('value="3030"', false);
    }
}
