<?php

namespace Tests\Feature\Api\V1\Customer;

use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Sleep;

/**
 * پرداخت در اپ «ماهرو» با مرورگر بیرونی: pay → checkout_url امضاشده → درگاه → /payments/return → صفحه‌ی برگشت به اپ
 * (mahru://payment) → GET payments/{tx}. منطق مالی همان وب.
 */
class CustomerPaymentApiTest extends CustomerApiTestCase
{
    use \Tests\Concerns\EnablesAllGateways;

    protected function setUp(): void
    {
        parent::setUp();
        Sleep::fake();
        Notification::fake();
        $this->salon->paymentGateways()->delete();
        $this->salon->paymentGateways()->create(['driver' => 'zibal', 'credentials' => ['merchant' => 'zibal'], 'priority' => 1]);
    }

    private function fakeZibal(int $trackId, int $verifyAmountRial): void
    {
        Http::fake([
            'gateway.zibal.ir/v1/request' => Http::response(['result' => 100, 'trackId' => $trackId]),
            'gateway.zibal.ir/v1/verify' => Http::response(['result' => 100, 'amount' => $verifyAmountRial, 'refNumber' => 777]),
        ]);
    }

    /** مرورگر بیرونی: بازگشت از بانک به /payments/return و دنبال کردن 303 به صفحه‌ی اپ */
    private function bankReturns(PaymentTransaction $tx, array $params): \Illuminate\Testing\TestResponse
    {
        $this->app['auth']->forgetGuards();
        $location = $this->withoutToken()->get("/payments/return/{$tx->public_id}?".http_build_query($params))->assertStatus(303)->headers->get('Location');

        return $this->get($location);
    }

    public function test_paying_returns_a_signed_checkout_link_that_leads_to_the_gateway(): void
    {
        $this->fakeZibal(41, 600000);
        $booking = $this->bookingFor($this->customer);

        $pay = $this->api()->postJson("/api/v1/customer/bookings/{$booking->id}/pay")
            ->assertOk()
            ->assertJsonPath('data.state', 'redirect')
            ->assertJsonPath('data.gateway', 'zibal')
            ->assertJsonPath('data.expires_in', 900);

        $tx = PaymentTransaction::sole();
        $this->assertSame($tx->public_id, $pay->json('data.transaction'));
        $this->assertStringContainsString('/s/rasta/app/payment/callback', $tx->callback_url);

        $url = $pay->json('data.checkout_url');
        $this->withoutToken()->get($url)->assertRedirect('https://gateway.zibal.ir/start/41');
        $this->get(preg_replace('/signature=[^&]+/', 'signature=forged', $url))->assertForbidden();
        $this->travel(16)->minutes();
        $this->get($url)->assertForbidden();
    }

    public function test_the_full_trip_through_the_bank_confirms_the_booking_and_sends_the_customer_back_to_the_app(): void
    {
        $this->fakeZibal(42, 600000);
        $booking = $this->bookingFor($this->customer);
        $this->api()->postJson("/api/v1/customer/bookings/{$booking->id}/pay")->assertOk();
        $tx = PaymentTransaction::sole();

        $this->bankReturns($tx, ['trackId' => 42, 'success' => 1, 'status' => 2])
            ->assertOk()
            ->assertSee('پرداخت انجام شد')
            ->assertSee('mahru://payment?tx='.$tx->public_id.'&amp;state=paid&amp;purpose=booking', false)
            ->assertHeader('Cache-Control', 'no-store, private');

        $this->assertSame('paid', $booking->fresh()->payment_status);
        $this->assertSame('pending', $booking->fresh()->status);

        $this->api()->getJson("/api/v1/customer/payments/{$tx->public_id}")
            ->assertOk()
            ->assertJsonPath('data.state', 'paid')
            ->assertJsonPath('data.booking.payment_status', 'paid')
            ->assertJsonPath('data.ref_id', '777');

        // رفرش صفحه‌ی برگشت: دوباره verify نمی‌شود
        $this->bankReturns($tx, ['trackId' => 42, 'success' => 1, 'status' => 2])->assertSee('پرداخت انجام شد');
        $this->assertCount(1, Http::recorded(fn (Request $r) => str_contains($r->url(), '/v1/verify')));
    }

    public function test_a_failed_bank_return_reports_failure_and_cancels_the_booking(): void
    {
        Http::fake([
            'gateway.zibal.ir/v1/request' => Http::response(['result' => 100, 'trackId' => 43]),
            'gateway.zibal.ir/v1/verify' => Http::response(['result' => 202]),
        ]);
        $booking = $this->bookingFor($this->customer);
        $this->api()->postJson("/api/v1/customer/bookings/{$booking->id}/pay");

        $this->bankReturns(PaymentTransaction::sole(), ['trackId' => 43, 'success' => 0, 'status' => 3])
            ->assertOk()->assertSee('پرداخت ناموفق بود')->assertSee('state=failed', false);

        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->api()->getJson('/api/v1/customer/payments/'.PaymentTransaction::sole()->public_id)->assertJsonPath('data.state', 'failed');
    }

    public function test_the_wallet_can_pay_the_whole_prepayment_without_the_bank(): void
    {
        $this->customer->getOrCreateWallet()->update(['balance' => 100000]);
        $booking = $this->bookingFor($this->customer);

        $this->api()->postJson("/api/v1/customer/bookings/{$booking->id}/pay", ['wallet_amount' => 60000])
            ->assertOk()->assertJsonPath('data.state', 'paid')->assertJsonPath('data.booking.payment_status', 'paid');

        $this->assertSame(40000.0, (float) $this->customer->getOrCreateWallet()->fresh()->balance);
        $this->assertSame(0, PaymentTransaction::count());
    }

    public function test_a_split_payment_keeps_the_wallet_share_on_the_transaction(): void
    {
        $this->fakeZibal(44, 400000);
        $this->customer->getOrCreateWallet()->update(['balance' => 20000]);
        $booking = $this->bookingFor($this->customer);

        $this->api()->postJson("/api/v1/customer/bookings/{$booking->id}/pay", ['wallet_amount' => 20000])->assertJsonPath('data.state', 'redirect');
        $tx = PaymentTransaction::sole();
        $this->assertSame(20000.0, $tx->wallet_toman);
        $this->assertSame(400000, $tx->amount_rial);

        $this->bankReturns($tx, ['trackId' => 44, 'success' => 1, 'status' => 2])->assertSee('پرداخت انجام شد');

        $this->assertSame('wallet_gateway', $booking->fresh()->payment_details['method']);
        $this->assertSame('settled', $tx->fresh()->wallet_state);
    }

    public function test_a_cancelled_or_paid_booking_is_not_payable(): void
    {
        $cancelled = $this->bookingFor($this->customer, ['status' => 'cancelled', 'cancelled_by' => 'system']);
        $paid = $this->bookingFor($this->customer, ['status' => 'confirmed', 'payment_status' => 'paid']);

        $this->api()->postJson("/api/v1/customer/bookings/{$cancelled->id}/pay")
            ->assertStatus(409)->assertJsonPath('error.code', 'booking_not_payable');
        $this->api()->postJson("/api/v1/customer/bookings/{$paid->id}/pay")->assertOk()->assertJsonPath('data.state', 'paid');
        $this->assertSame(0, PaymentTransaction::count());
    }

    public function test_the_return_page_never_acts_without_a_transaction_of_this_salon_and_purpose(): void
    {
        $this->fakeZibal(45, 600000);
        $booking = $this->bookingFor($this->customer);

        $this->get("/s/rasta/app/payment/callback?booking={$booking->id}&trackId=45&success=1&status=2")
            ->assertOk()->assertSee('پرداخت پیدا نشد');
        $this->get("/s/rasta/app/payment/callback?booking={$booking->id}&tx=".\Illuminate\Support\Str::uuid())
            ->assertOk()->assertSee('پرداخت پیدا نشد');

        $this->assertSame('unpaid', $booking->fresh()->payment_status);
        Http::assertNothingSent();
    }

    public function test_a_wallet_charge_transaction_cannot_confirm_a_booking(): void
    {
        $this->fakeZibal(49, 600000);
        $booking = $this->bookingFor($this->customer);
        $this->api()->postJson('/api/v1/customer/wallet/charge', ['amount' => 60000]);
        $charge = PaymentTransaction::sole();

        $this->get("/s/rasta/app/payment/callback?booking={$booking->id}&tx={$charge->public_id}&trackId=49&success=1&status=2")
            ->assertSee('پرداخت پیدا نشد');

        $this->assertSame('unpaid', $booking->fresh()->payment_status);
    }

    public function test_the_booking_comes_from_the_transaction_not_from_the_url(): void
    {
        $this->fakeZibal(46, 600000);
        $mine = $this->bookingFor($this->customer);
        $other = $this->bookingFor(User::factory()->create(['salon_id' => $this->salon->id, 'user_type' => 'customer']));
        $this->api()->postJson("/api/v1/customer/bookings/{$mine->id}/pay");
        $tx = PaymentTransaction::sole();

        $this->get("/s/rasta/app/payment/callback?booking={$other->id}&tx={$tx->public_id}&trackId=46&success=1&status=2")->assertOk();

        $this->assertSame('paid', $mine->fresh()->payment_status);
        $this->assertSame('unpaid', $other->fresh()->payment_status);
    }

    public function test_another_customers_payment_status_is_404(): void
    {
        $this->fakeZibal(47, 600000);
        $booking = $this->bookingFor($this->customer);
        $this->api()->postJson("/api/v1/customer/bookings/{$booking->id}/pay");
        $stranger = $this->customerOf($this->salon, '09127770000');

        $this->api($this->tokenFor($stranger))->getJson('/api/v1/customer/payments/'.PaymentTransaction::sole()->public_id)->assertNotFound();
    }

    public function test_wallet_charge_goes_through_the_bank_and_is_credited_once(): void
    {
        $this->fakeZibal(48, 1000000);

        $this->api()->getJson('/api/v1/customer/wallet/charge?amount=100000')
            ->assertOk()->assertJsonPath('data.online_payment', true)->assertJsonPath('data.gateways.0.total', 100000);
        $this->api()->postJson('/api/v1/customer/wallet/charge', ['amount' => 5000])->assertStatus(422);
        $charge = $this->api()->postJson('/api/v1/customer/wallet/charge', ['amount' => 100000])
            ->assertOk()->assertJsonPath('data.state', 'redirect');
        $tx = PaymentTransaction::sole();
        $this->assertSame('wallet_charge', $tx->purpose);
        $this->assertStringContainsString('/s/rasta/app/wallet/callback', $tx->callback_url);

        $this->api()->getJson("/api/v1/customer/payments/{$tx->public_id}")->assertJsonPath('data.state', 'pending');

        $this->bankReturns($tx, ['trackId' => 48, 'success' => 1, 'status' => 2])
            ->assertOk()->assertSee('کیف پول شارژ شد')->assertSee('purpose=wallet_charge', false);
        $this->bankReturns($tx, ['trackId' => 48, 'success' => 1, 'status' => 2])->assertSee('قبلاً');

        $this->assertSame(100000.0, (float) $this->customer->getOrCreateWallet()->fresh()->balance);
        $this->api()->getJson("/api/v1/customer/payments/{$tx->public_id}")
            ->assertJsonPath('data.state', 'paid')->assertJsonPath('data.credited', true)->assertJsonPath('data.wallet_balance', 100000);
        $this->assertNotNull($charge->json('data.checkout_url'));
    }

    public function test_crediting_the_same_charge_twice_only_adds_the_money_once(): void
    {
        $this->fakeZibal(50, 1000000);
        $this->api()->postJson('/api/v1/customer/wallet/charge', ['amount' => 100000]);
        $tx = PaymentTransaction::sole();
        $charges = app(\App\Services\Payment\WalletChargeService::class);

        // دو callback هم‌زمان که هر دو از verify درگاه رد شده‌اند
        $this->assertSame(100000.0, $charges->credit($this->customer, 100000, ['ref_id' => '1'], $tx));
        $this->assertNull($charges->credit($this->customer, 100000, ['ref_id' => '1'], $tx));

        $this->assertSame(100000.0, (float) $this->customer->getOrCreateWallet()->fresh()->balance);
    }
}
