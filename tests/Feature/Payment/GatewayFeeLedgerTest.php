<?php

namespace Tests\Feature\Payment;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\PaymentTransaction;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * ⭐ مرحله‌ی ۱ چند درگاه (۲۰۲۶-۰۹-۲۵) — کارمزد درگاه روی مبلغ مشتری، دفتر تراکنش‌ها و مسیر کامل با
 * درگاه‌های جدید (زیبال با redirect، آسان پرداخت با فرم POST).
 */
class GatewayFeeLedgerTest extends TestCase
{
    use RefreshDatabase;

    private Salon $salon;

    protected function setUp(): void
    {
        parent::setUp();
        $this->salon = app(CurrentSalon::class)->get();
        $this->salon->paymentGateways()->delete();
    }

    private function booking(User $user, int $prepayment = 60000): Booking
    {
        return Booking::factory()->create([
            'user_id' => $user->id,
            'service_id' => BeautyService::factory()->create(['price' => 200000])->id,
            'specialist_id' => Specialist::factory()->create()->id,
            'payment_status' => 'unpaid',
            'status' => 'pending_payment',
            'prepayment_amount' => $prepayment,
        ]);
    }

    public function test_the_fee_is_added_to_what_the_gateway_charges_and_recorded_on_the_transaction(): void
    {
        $this->salon->paymentGateways()->create(['driver' => 'zibal', 'credentials' => ['merchant' => 'zibal'], 'priority' => 1, 'fee_percent' => 1, 'fee_fixed_toman' => 400]);
        Http::fake([
            'gateway.zibal.ir/v1/request' => Http::response(['result' => 100, 'trackId' => 555]),
            'gateway.zibal.ir/v1/verify' => Http::response(['result' => 100, 'status' => 1, 'amount' => 610000, 'refNumber' => 42]),
        ]);
        $user = User::factory()->create();
        $booking = $this->booking($user);

        $this->actingAs($user)->post(route('payment.process', $booking))->assertRedirect('https://gateway.zibal.ir/start/555');

        // ۶۰٬۰۰۰ تومان + (۱٪ = ۶۰۰ + ۴۰۰) = ۶۱٬۰۰۰ تومان = ۶۱۰٬۰۰۰ ریال
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/v1/request') && $r['amount'] === 610000);
        $tx = PaymentTransaction::where('payable_id', $booking->id)->sole();
        $this->assertSame(610000, $tx->amount_rial);
        $this->assertSame(10000, $tx->fee_rial);

        $bounce = $this->get("/payments/return/{$tx->public_id}?trackId=555&success=1&status=2");
        $this->actingAs($user)->get($bounce->headers->get('Location'));

        $booking->refresh();
        $this->assertSame('paid', $booking->payment_status);
        $this->assertSame(1000, $booking->payment_details['gateway_fee']);
        $this->assertSame('zibal', $booking->payment_details['gateway']);
        $this->assertSame(60000.0, (float) $booking->prepayment_amount, 'کارمزد به پیش‌پرداخت نوبت اضافه نمی‌شه');
    }

    public function test_failover_recomputes_the_fee_for_the_gateway_actually_used(): void
    {
        $this->salon->paymentGateways()->create(['driver' => 'vandar', 'credentials' => ['api_key' => 'k'], 'priority' => 1, 'fee_fixed_toman' => 5000]);
        $this->salon->paymentGateways()->create(['driver' => 'zibal', 'credentials' => ['merchant' => 'zibal'], 'priority' => 2, 'fee_fixed_toman' => 1000]);
        Http::fake([
            'ipg.vandar.io/*' => Http::response('', 502),
            'gateway.zibal.ir/v1/request' => Http::response(['result' => 100, 'trackId' => 777]),
        ]);
        $user = User::factory()->create();
        $booking = $this->booking($user);

        $this->actingAs($user)->post(route('payment.process', $booking))->assertRedirect('https://gateway.zibal.ir/start/777');

        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'vandar') && $r['amount'] === 650000);
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'zibal') && $r['amount'] === 610000);
        $tx = PaymentTransaction::where('payable_id', $booking->id)->sole();
        $this->assertSame('zibal', $tx->driver);
        $this->assertSame(610000, $tx->amount_rial);
    }

    public function test_the_customers_chosen_gateway_is_used_first(): void
    {
        $this->salon->paymentGateways()->create(['driver' => 'zibal', 'credentials' => ['merchant' => 'zibal'], 'priority' => 1]);
        $vandar = $this->salon->paymentGateways()->create(['driver' => 'vandar', 'credentials' => ['api_key' => 'k'], 'priority' => 2]);
        Http::fake(['ipg.vandar.io/api/v4/send' => Http::response(['status' => 1, 'token' => 'VT'])]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('payment.process', $this->booking($user)), ['gateway_id' => $vandar->id])
            ->assertRedirect('https://ipg.vandar.io/v4/VT');
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), 'zibal'));
    }

    public function test_a_gateway_of_another_salon_cannot_be_chosen(): void
    {
        $this->salon->paymentGateways()->create(['driver' => 'zibal', 'credentials' => ['merchant' => 'zibal'], 'priority' => 1]);
        $foreign = Salon::factory()->create()->paymentGateways()->create(['driver' => 'vandar', 'credentials' => ['api_key' => 'k'], 'priority' => 1]);
        Http::fake(['gateway.zibal.ir/v1/request' => Http::response(['result' => 100, 'trackId' => 1])]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('payment.process', $this->booking($user)), ['gateway_id' => $foreign->id])
            ->assertRedirect('https://gateway.zibal.ir/start/1');
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), 'vandar'));
    }

    public function test_asanpardakht_sends_the_customer_with_an_auto_submitting_post_form_and_verifies_by_invoice_id(): void
    {
        $this->salon->paymentGateways()->create(['driver' => 'asanpardakht', 'credentials' => ['merchant_config_id' => '99', 'username' => 'u', 'password' => 'p'], 'priority' => 1]);
        Http::fake([
            'ipgrest.asanpardakht.ir/v1/Time' => Http::response('"20260925 101500"'),
            'ipgrest.asanpardakht.ir/v1/Token' => Http::response('"REF-XYZ"'),
            'ipgrest.asanpardakht.ir/v1/TranResult*' => Http::response(['payGateTranID' => 31, 'rrn' => 'RRN', 'amount' => 600000, 'cardNumber' => '6037****0000']),
            'ipgrest.asanpardakht.ir/v1/Verify' => Http::response('', 200),
            'ipgrest.asanpardakht.ir/v1/Settlement' => Http::response('', 200),
        ]);
        $user = User::factory()->create();
        $booking = $this->booking($user);

        $this->actingAs($user)->post(route('payment.process', $booking))
            ->assertOk()
            ->assertSee('action="https://asan.shaparak.ir"', false)
            ->assertSee('name="RefId" value="REF-XYZ"', false);

        $tx = PaymentTransaction::where('payable_id', $booking->id)->sole();
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/Token') && $r['localInvoiceId'] === $tx->id);

        // آسان پرداخت با POST cross-site (بدون کوکی) برمی‌گرده
        $bounce = $this->post("/payments/return/{$tx->public_id}", ['ReturningParams' => 'opaque']);
        $bounce->assertStatus(303);
        $this->actingAs($user)->get($bounce->headers->get('Location'));

        $this->assertSame('paid', $booking->fresh()->payment_status);
        $this->assertSame('RRN', $tx->fresh()->ref_id);
    }

    public function test_wallet_top_up_credits_the_base_amount_not_the_fee(): void
    {
        $this->salon->paymentGateways()->create(['driver' => 'zibal', 'credentials' => ['merchant' => 'zibal'], 'priority' => 1, 'fee_fixed_toman' => 2000]);
        Http::fake([
            'gateway.zibal.ir/v1/request' => Http::response(['result' => 100, 'trackId' => 9]),
            'gateway.zibal.ir/v1/verify' => Http::response(['result' => 100, 'status' => 1, 'amount' => 520000, 'refNumber' => 1]),
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('wallet.charge.process'), ['amount' => '50000']);
        $tx = PaymentTransaction::where('purpose', 'wallet_charge')->sole();
        $this->assertSame(520000, $tx->amount_rial);

        $bounce = $this->get("/payments/return/{$tx->public_id}?trackId=9&success=1");
        $this->actingAs($user)->get($bounce->headers->get('Location'));

        $this->assertSame(50000.0, (float) $user->getOrCreateWallet()->fresh()->balance);
    }
}
