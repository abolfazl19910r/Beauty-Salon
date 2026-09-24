<?php

namespace Tests\Feature\Payment;

use App\Models\PaymentTransaction;
use App\Models\Salon;
use App\Models\User;
use App\Services\PaymentService;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * ⭐ دفتر واحد تراکنش‌ها + آدرس بازگشت مشترک (لایه‌ی چند درگاه — مرحله‌ی ۰ بخش ۲، ۲۰۲۶-۰۹-۲۵).
 */
class PaymentTransactionLedgerTest extends TestCase
{
    use RefreshDatabase;

    private const MERCHANT = 'aaaaaaaa-1111-2222-3333-bbbbbbbbbbbb';

    protected function setUp(): void
    {
        parent::setUp();

        app(CurrentSalon::class)->get()->update(['zarinpal_merchant_id' => self::MERCHANT]);
        Http::fake([
            '*request.json' => Http::response(['data' => ['code' => 100, 'authority' => 'A-LEDGER']], 200),
            '*verify.json' => Http::response(['data' => ['code' => 100, 'ref_id' => 'REF-LEDGER', 'card_pan' => '5022***1234']], 200),
        ]);
    }

    public function test_starting_a_payment_records_a_pending_transaction_and_sends_the_shared_return_url(): void
    {
        $user = User::factory()->create();

        $result = app(PaymentService::class)->createWalletChargePayment($user, 25000);

        $tx = PaymentTransaction::where('public_id', $result['transaction'])->sole();
        $this->assertSame('pending', $tx->status);
        $this->assertSame('wallet_charge', $tx->purpose);
        $this->assertSame(250000, $tx->amount_rial);
        $this->assertSame('A-LEDGER', $tx->token);
        $this->assertSame(app(CurrentSalon::class)->id(), $tx->salon_id);
        $this->assertSame(route('wallet.charge.callback'), $tx->callback_url);

        Http::assertSent(fn ($r) => str_ends_with($r->url(), 'request.json')
            && $r['callback_url'] === route('payments.return', ['publicId' => $tx->public_id]));
    }

    public function test_return_route_bounces_get_and_cross_site_post_to_the_business_callback_as_get(): void
    {
        $tx = PaymentTransaction::create([
            'salon_id' => app(CurrentSalon::class)->id(), 'driver' => 'zarinpal', 'purpose' => 'wallet_charge',
            'amount_rial' => 1000, 'callback_url' => 'https://salon.test/wallet/charge/callback',
        ]);

        $this->get("/payments/return/{$tx->public_id}?Authority=A1&Status=OK")
            ->assertStatus(303)
            ->assertRedirect('https://salon.test/wallet/charge/callback?Authority=A1&Status=OK&tx='.$tx->public_id);

        // بانک‌ها: POST بدون کوکی و بدون CSRF token
        $this->post("/payments/return/{$tx->public_id}", ['RefNum' => 'R9', 'State' => 'OK', 'booking' => '999', 'tx' => 'forged'])
            ->assertStatus(303)
            ->assertRedirect('https://salon.test/wallet/charge/callback?RefNum=R9&State=OK&tx='.$tx->public_id);
    }

    public function test_unknown_transaction_is_not_found(): void
    {
        $this->get('/payments/return/'.\Illuminate\Support\Str::uuid())->assertNotFound();
    }

    public function test_verification_uses_the_recorded_amount_and_marks_the_transaction_paid(): void
    {
        $user = User::factory()->create();
        $service = app(PaymentService::class);
        $public = $service->createWalletChargePayment($user, 25000)['transaction'];

        // مبلغ دستکاری‌شده در callback کسب‌وکار نباید اثری داشته باشه — مبلغ از ردیف تراکنش
        $result = $service->verifyWalletChargePayment(new Request(['Authority' => 'A-LEDGER', 'Status' => 'OK', 'tx' => $public]), 1);

        $this->assertTrue($result['success']);
        Http::assertSent(fn ($r) => str_ends_with($r->url(), 'verify.json') && $r['amount'] === 250000);
        $tx = PaymentTransaction::where('public_id', $public)->sole();
        $this->assertSame('paid', $tx->status);
        $this->assertSame('REF-LEDGER', $tx->ref_id);
        $this->assertNotNull($tx->verified_at);
    }

    public function test_a_second_callback_for_a_paid_wallet_charge_is_refused(): void
    {
        $user = User::factory()->create();
        $service = app(PaymentService::class);
        $public = $service->createWalletChargePayment($user, 25000)['transaction'];
        $callback = new Request(['Authority' => 'A-LEDGER', 'Status' => 'OK', 'tx' => $public]);

        $this->assertTrue($service->verifyWalletChargePayment($callback, 25000)['success']);
        $this->assertFalse($service->verifyWalletChargePayment($callback, 25000)['success']);
    }

    public function test_a_transaction_of_another_salon_is_ignored(): void
    {
        $other = Salon::factory()->create(['zarinpal_merchant_id' => 'cccccccc-1111-2222-3333-dddddddddddd']);
        $foreign = PaymentTransaction::create([
            'salon_id' => $other->id, 'gateway_id' => $other->paymentGateways()->value('id'), 'driver' => 'zarinpal',
            'purpose' => 'wallet_charge', 'amount_rial' => 10, 'callback_url' => 'x',
        ]);

        app(PaymentService::class)->verifyWalletChargePayment(new Request(['Authority' => 'A-LEDGER', 'Status' => 'OK', 'tx' => $foreign->public_id]), 25000);

        Http::assertNotSent(fn ($r) => str_ends_with($r->url(), 'verify.json') && $r['merchant_id'] === 'cccccccc-1111-2222-3333-dddddddddddd');
        $this->assertSame('pending', $foreign->fresh()->status);
    }

    public function test_zarinpal_production_endpoints_follow_the_current_official_docs(): void
    {
        config(['services.zarinpal.sandbox' => false]);
        $user = User::factory()->create();

        $result = app(PaymentService::class)->createWalletChargePayment($user, 1000);

        $this->assertSame('https://payment.zarinpal.com/pg/StartPay/A-LEDGER', $result['payment_url']);
        Http::assertSent(fn ($r) => $r->url() === 'https://payment.zarinpal.com/pg/v4/payment/request.json');
    }
}
