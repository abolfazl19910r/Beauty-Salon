<?php

namespace Tests\Feature\Payment;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\PaymentTransaction;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Payments\Drivers\SamanDriver;
use App\Payments\GatewayManager;
use App\Payments\GatewayReceipt;
use App\Payments\GatewayStartRequest;
use App\Payments\GatewayVerifyRequest;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

/**
 * ⭐ مرحله‌ی ۲ چند درگاه — درگاه مستقیم بانک سامان (سپ). پاسخ‌ها عین نمونه‌های مستند رسمی سپ (نگارش ۳٫۲):
 * توکن با فرم POST، بازگشت POST بدون کوکی، verify با RefNum + TerminalNumber، و قانون مستند که
 * «جلوگیری از مصرف دوباره‌ی رسید به عهده‌ی پذیرنده است».
 */
class SamanGatewayTest extends TestCase
{
    use RefreshDatabase;

    private const TERMINAL = '13012345';

    private Salon $salon;

    protected function setUp(): void
    {
        parent::setUp();
        $this->salon = app(CurrentSalon::class)->get();
        $this->salon->paymentGateways()->delete();
        Sleep::fake();
    }

    private function driver(): SamanDriver
    {
        return new SamanDriver(['terminal_id' => self::TERMINAL]);
    }

    private function tx(int $amountRial = 250000, string $token = 'TOKEN-A'): PaymentTransaction
    {
        return PaymentTransaction::create([
            'salon_id' => $this->salon->id, 'driver' => 'saman', 'purpose' => 'booking',
            'amount_rial' => $amountRial, 'token' => $token, 'callback_url' => 'https://salon.test/cb',
        ]);
    }

    /** بدنه‌ی POST بازگشت سپ برای یک پرداخت موفق (پارامترهای صفحه‌ی ۱۱ و ۲۳ مستند). */
    private function sepReturn(PaymentTransaction $tx, string $refNum = 'jJnBmy/IojtTemplUH5ke9ULCGtDtb', array $override = []): array
    {
        return array_merge([
            'MID' => self::TERMINAL, 'TerminalId' => self::TERMINAL, 'State' => 'OK', 'Status' => '2',
            'RRN' => '14226761817', 'Rrn' => '14226761817', 'RefNum' => $refNum, 'ResNum' => (string) $tx->id,
            'TraceNo' => '100428', 'Amount' => (string) $tx->amount_rial, 'Wage' => '0',
            'SecurePan' => '621986******8080', 'Token' => $tx->token,
        ], $override);
    }

    /** پاسخ VerifyTransaction (نمونه‌ی صفحه‌ی ۱۷ مستند). */
    private static function verified(int $amountRial, string $refNum = 'jJnBmy/IojtTemplUH5ke9ULCGtDtb', int $code = 0): array
    {
        return [
            'TransactionDetail' => [
                'RRN' => '14226761817', 'RefNum' => $refNum, 'MaskedPan' => '621986****8080',
                'HashedPan' => 'b96a14400c3a59249e87c300ecc06e5920327e70220213b5bbb7d7b2410f7e0d',
                'TerminalNumber' => (int) self::TERMINAL, 'OrginalAmount' => $amountRial, 'AffectiveAmount' => $amountRial,
                'StraceDate' => '2019-09-16 18:11:06', 'StraceNo' => '100428',
            ],
            'ResultCode' => $code, 'ResultDescription' => 'عملیات با موفقیت انجام شد', 'Success' => true,
        ];
    }

    private function verifyWith(PaymentTransaction $tx, array $callback): \App\Payments\GatewayVerifyResult
    {
        return $this->driver()->verify(new GatewayVerifyRequest($tx->amount_rial, $callback, $tx->token, $tx->id));
    }

    // ── شروع پرداخت ──

    public function test_start_requests_a_token_in_rial_and_returns_a_post_form_to_the_payment_page(): void
    {
        Http::fake(['sep.shaparak.ir/OnlinePG/OnlinePG' => Http::response(['status' => 1, 'token' => '2c3c1fefac5a48geb9f9be7e445dd9b2'])]);

        $result = $this->driver()->start(new GatewayStartRequest(250000, 'https://salon.test/payments/return/abc', 'پیش پرداخت', '۰۹۱۲۱۲۳۴۵۶۷', null, '7', 42));

        $this->assertTrue($result->success);
        $this->assertSame('POST', $result->method);
        $this->assertSame('https://sep.shaparak.ir/OnlinePG/OnlinePG', $result->redirectUrl);
        $this->assertSame(['Token' => '2c3c1fefac5a48geb9f9be7e445dd9b2'], $result->formFields);
        $this->assertSame('2c3c1fefac5a48geb9f9be7e445dd9b2', $result->token);
        Http::assertSent(fn (Request $r) => $r['action'] === 'token' && $r['TerminalId'] === self::TERMINAL
            && $r['Amount'] === 250000 && $r['ResNum'] === '42'
            && $r['RedirectUrl'] === 'https://salon.test/payments/return/abc' && $r['CellNumber'] === '09121234567');
    }

    public function test_an_unusable_mobile_is_not_sent(): void
    {
        Http::fake(['sep.shaparak.ir/*' => Http::response(['status' => 1, 'token' => 'T'])]);

        $this->driver()->start(new GatewayStartRequest(250000, 'https://salon.test/r', 'x', '021-1234', transactionId: 1));

        Http::assertSent(fn (Request $r) => ! array_key_exists('CellNumber', $r->data()));
    }

    public function test_token_errors_map_to_messages_and_only_real_outages_fail_over(): void
    {
        Http::fakeSequence('sep.shaparak.ir/*')
            ->push(['status' => -1, 'errorCode' => '8', 'errorDesc' => 'MerchantIpAddressIsInvalid'])
            ->push('', 503);

        $ip = $this->driver()->start(new GatewayStartRequest(250000, 'https://salon.test/r', 'x', transactionId: 1));
        $this->assertFalse($ip->success);
        $this->assertFalse($ip->retryable, 'IP ثبت‌نشده خطای پیکربندیه، نه قطعی');
        $this->assertStringContainsString('IP سرور', $ip->message);

        $this->assertTrue($this->driver()->start(new GatewayStartRequest(250000, 'https://salon.test/r', 'x', transactionId: 1))->retryable);

        Http::fake(['sep.shaparak.ir/*' => Http::failedConnection()]);
        $this->assertTrue($this->driver()->start(new GatewayStartRequest(250000, 'https://salon.test/r', 'x', transactionId: 1))->retryable);
    }

    // ── تایید ──

    public function test_verify_sends_ref_num_and_terminal_and_checks_the_original_amount(): void
    {
        $tx = $this->tx();
        Http::fake(['sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/VerifyTransaction' => Http::response(self::verified(250000))]);

        $result = $this->verifyWith($tx, $this->sepReturn($tx));

        $this->assertTrue($result->success);
        $this->assertSame('14226761817', $result->refId);
        $this->assertSame('621986****8080', $result->cardPan);
        $this->assertSame('jJnBmy/IojtTemplUH5ke9ULCGtDtb', $tx->fresh()->gateway_receipt);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/VerifyTransaction')
            && $r['RefNum'] === 'jJnBmy/IojtTemplUH5ke9ULCGtDtb' && $r['TerminalNumber'] === (int) self::TERMINAL);
    }

    public function test_the_documented_response_with_a_space_in_the_detail_key_is_understood(): void
    {
        $tx = $this->tx();
        $body = self::verified(250000);
        $body[' TransactionDetail'] = $body['TransactionDetail'];
        unset($body['TransactionDetail']);
        Http::fake(['sep.shaparak.ir/*' => Http::response($body)]);

        $this->assertTrue($this->verifyWith($tx, $this->sepReturn($tx))->success);
    }

    public function test_a_cancelled_or_failed_return_is_never_verified(): void
    {
        Http::fake();
        $tx = $this->tx();

        $cancelled = $this->verifyWith($tx, $this->sepReturn($tx, '', ['State' => 'CanceledByUser', 'Status' => '1']));
        $this->assertFalse($cancelled->success);
        $this->assertTrue($cancelled->cancelledByUser);

        $timeout = $this->verifyWith($tx, $this->sepReturn($tx, '', ['State' => 'SessionIsNull', 'Status' => null]));
        $this->assertFalse($timeout->success);
        $this->assertFalse($timeout->cancelledByUser);
        $this->assertStringContainsString('زمان پرداخت', $timeout->message);

        Http::assertNothingSent();
        $this->assertNull($tx->fresh()->gateway_receipt);
    }

    public function test_a_return_belonging_to_another_transaction_or_terminal_is_rejected_without_verifying(): void
    {
        Http::fake();
        $tx = $this->tx();

        $this->assertFalse($this->verifyWith($tx, $this->sepReturn($tx, 'R1', ['ResNum' => '999999']))->success);
        $this->assertFalse($this->verifyWith($tx, $this->sepReturn($tx, 'R1', ['Token' => 'SOMEONE-ELSES']))->success);
        $this->assertFalse($this->verifyWith($tx, $this->sepReturn($tx, 'R1', ['TerminalId' => '99', 'MID' => '99']))->success);

        Http::assertNothingSent();
    }

    public function test_a_receipt_already_used_by_another_transaction_is_refused_before_verify(): void
    {
        $first = $this->tx(250000, 'T1');
        $second = $this->tx(250000, 'T2');
        Http::fake(['sep.shaparak.ir/*' => Http::response(self::verified(250000, 'SAME-RECEIPT'))]);

        $this->assertTrue($this->verifyWith($first, $this->sepReturn($first, 'SAME-RECEIPT'))->success);

        // سپ همون رسید رو دوباره تایید می‌کرد (ResultCode ۲) — ولی ما نباید حتی بپرسیم
        Http::fake(['sep.shaparak.ir/*' => Http::response(self::verified(250000, 'SAME-RECEIPT', 2))]);
        $replay = $this->verifyWith($second, $this->sepReturn($second, 'SAME-RECEIPT'));

        $this->assertFalse($replay->success);
        $this->assertStringContainsString('قبلاً', $replay->message);
        Http::assertNothingSent();
        $this->assertNull($second->fresh()->gateway_receipt);
    }

    public function test_a_retry_for_the_same_transaction_accepts_the_duplicate_result_code(): void
    {
        $tx = $this->tx();
        Http::fake(['sep.shaparak.ir/*' => Http::sequence()
            ->push(self::verified(250000))
            ->push(self::verified(250000, 'jJnBmy/IojtTemplUH5ke9ULCGtDtb', 2))]);

        $this->assertTrue($this->verifyWith($tx, $this->sepReturn($tx))->success);
        $this->assertTrue($this->verifyWith($tx, $this->sepReturn($tx))->success, 'پاسخ اول گم شده و دوباره تایید می‌کنیم');
    }

    public function test_an_amount_mismatch_is_reversed_and_rejected(): void
    {
        $tx = $this->tx(250000);
        Http::fake([
            'sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/VerifyTransaction' => Http::response(self::verified(10000)),
            'sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/ReverseTransaction' => Http::response(self::verified(10000)),
        ]);

        $result = $this->verifyWith($tx, $this->sepReturn($tx));

        $this->assertFalse($result->success);
        $this->assertTrue($result->raw['reversed']);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/ReverseTransaction')
            && $r['RefNum'] === 'jJnBmy/IojtTemplUH5ke9ULCGtDtb' && $r['TerminalNumber'] === (int) self::TERMINAL);
    }

    public function test_negative_result_codes_fail_with_the_documented_message_and_are_not_reversed(): void
    {
        $tx = $this->tx();
        Http::fake(['sep.shaparak.ir/*' => Http::response(['ResultCode' => -6, 'ResultDescription' => 'expired', 'Success' => false])]);

        $result = $this->verifyWith($tx, $this->sepReturn($tx));

        $this->assertFalse($result->success);
        $this->assertStringContainsString('نیم ساعت', $result->message);
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), 'Reverse'));
    }

    public function test_verify_is_retried_when_the_bank_does_not_answer(): void
    {
        $tx = $this->tx();
        $calls = 0;
        Http::fake(['sep.shaparak.ir/*' => function () use (&$calls) {
            return ++$calls < 3 ? Http::failedConnection() : Http::response(self::verified(250000));
        }]);

        $this->assertTrue($this->verifyWith($tx, $this->sepReturn($tx))->success);
        $this->assertSame(3, $calls);
    }

    public function test_receipt_claim_is_bound_to_one_transaction_and_its_driver(): void
    {
        $a = $this->tx();
        $b = $this->tx();
        $zibal = PaymentTransaction::create(['driver' => 'zibal', 'purpose' => 'booking', 'amount_rial' => 1, 'callback_url' => 'x']);

        $this->assertTrue(GatewayReceipt::claim('saman', 'R-1', $a->id));
        $this->assertTrue(GatewayReceipt::claim('saman', 'R-1', $a->id), 'callback تکراری همون تراکنش');
        $this->assertFalse(GatewayReceipt::claim('saman', 'R-2', $a->id), 'تراکنش رسید دیگه‌ای داره');
        $this->assertFalse(GatewayReceipt::claim('saman', 'R-1', $b->id));
        $this->assertFalse(GatewayReceipt::claim('saman', 'R-9', $zibal->id), 'تراکنش درگاه دیگه');
        $this->assertTrue(GatewayReceipt::claim('saman', 'R-3', $b->id));
    }

    // ── کاتالوگ و صفحه‌ی مدیریت ──

    public function test_owner_can_add_saman_with_persian_digits_and_the_manager_builds_the_driver(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);

        $this->actingAs($owner)->get(route('admin.payment-gateways.index'))->assertOk()->assertSee('بانک سامان (سپ)');
        $this->actingAs($owner)->post(route('admin.payment-gateways.store'), [
            'driver' => 'saman', 'credentials' => ['terminal_id' => 'ABC'],
        ])->assertSessionHasErrors('credentials.terminal_id');
        $this->actingAs($owner)->post(route('admin.payment-gateways.store'), [
            'driver' => 'saman', 'credentials' => ['terminal_id' => '۱۳۰۱۲۳۴۵'], 'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $gateway = $this->salon->paymentGateways()->where('driver', 'saman')->sole();
        $this->assertSame(['terminal_id' => self::TERMINAL], $gateway->credentials);
        $this->assertInstanceOf(SamanDriver::class, app(GatewayManager::class)->driver($gateway));
    }

    // ── مسیر کامل پرداخت نوبت ──

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

    public function test_full_booking_payment_with_the_post_form_and_the_cookieless_post_return(): void
    {
        $this->salon->paymentGateways()->create(['driver' => 'saman', 'credentials' => ['terminal_id' => self::TERMINAL], 'priority' => 1, 'fee_fixed_toman' => 500]);
        Http::fake([
            'sep.shaparak.ir/OnlinePG/OnlinePG' => Http::response(['status' => 1, 'token' => 'SEP-TOKEN-1']),
            'sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/VerifyTransaction' => Http::response(self::verified(605000, 'RCPT-1')),
        ]);
        $user = User::factory()->create(['phone' => '09121234567']);
        $booking = $this->booking($user);

        // ۱) مشتری با فرم POST خودکار به صفحه‌ی پرداخت سپ فرستاده می‌شه
        $this->actingAs($user)->post(route('payment.process', $booking))
            ->assertOk()
            ->assertSee('action="https://sep.shaparak.ir/OnlinePG/OnlinePG"', false)
            ->assertSee('name="Token" value="SEP-TOKEN-1"', false);

        $tx = PaymentTransaction::where('payable_id', $booking->id)->sole();
        $this->assertSame('saman', $tx->driver);
        $this->assertSame(605000, $tx->amount_rial, '۶۰٬۰۰۰ + ۵۰۰ تومان کارمزد');
        Http::assertSent(fn (Request $r) => ($r['action'] ?? null) === 'token' && $r['ResNum'] === (string) $tx->id
            && $r['Amount'] === 605000 && $r['RedirectUrl'] === route('payments.return', ['publicId' => $tx->public_id]));

        // ۲) سپ با POST cross-site و بدون کوکی session برمی‌گرده → 303 به callback نوبت
        $this->app['auth']->forgetGuards();
        $bounce = $this->post("/payments/return/{$tx->public_id}", $this->sepReturn($tx, 'RCPT-1'));
        $bounce->assertStatus(303);
        $location = $bounce->headers->get('Location');
        $this->assertStringContainsString('RefNum=RCPT-1', $location);
        $this->assertStringContainsString('tx='.$tx->public_id, $location);

        // ۳) مرورگر با GET (کوکی Lax همراهشه) به callback می‌ره → verify → نوبت پرداخت‌شده
        $this->actingAs($user)->get($location);

        $booking->refresh();
        $this->assertSame('paid', $booking->payment_status);
        $this->assertSame('saman', $booking->payment_details['gateway']);
        $this->assertSame(500, $booking->payment_details['gateway_fee']);
        $tx->refresh();
        $this->assertSame('paid', $tx->status);
        $this->assertSame('14226761817', $tx->ref_id);
        $this->assertSame('RCPT-1', $tx->gateway_receipt);
        $this->assertSame('621986****8080', $tx->card_pan);

        // ۴) callback تکراری دوباره verify نمی‌زنه و نوبت دوباره ثبت نمی‌شه
        $this->actingAs($user)->get($location);
        Http::assertSentCount(2); // یک توکن + یک verify
    }

    public function test_a_used_receipt_cannot_pay_a_second_booking_of_the_same_amount(): void
    {
        $this->salon->paymentGateways()->create(['driver' => 'saman', 'credentials' => ['terminal_id' => self::TERMINAL], 'priority' => 1]);
        Http::fake([
            'sep.shaparak.ir/OnlinePG/OnlinePG' => Http::sequence()->push(['status' => 1, 'token' => 'T-1'])->push(['status' => 1, 'token' => 'T-2']),
            'sep.shaparak.ir/verifyTxnRandomSessionkey/*' => Http::response(self::verified(600000, 'RCPT-X')),
        ]);
        $user = User::factory()->create();
        $paid = $this->booking($user);
        $other = $this->booking($user);

        $this->actingAs($user)->post(route('payment.process', $paid));
        $this->actingAs($user)->post(route('payment.process', $other));
        $txPaid = PaymentTransaction::where('payable_id', $paid->id)->sole();
        $txOther = PaymentTransaction::where('payable_id', $other->id)->sole();

        $this->actingAs($user)->get($this->post("/payments/return/{$txPaid->public_id}", $this->sepReturn($txPaid, 'RCPT-X'))->headers->get('Location'));
        $this->assertSame('paid', $paid->fresh()->payment_status);

        // همون رسید با توکن و شماره‌ی خرید نوبت دوم
        $this->actingAs($user)->get($this->post("/payments/return/{$txOther->public_id}", $this->sepReturn($txOther, 'RCPT-X'))->headers->get('Location'));

        $this->assertNotSame('paid', $other->fresh()->payment_status);
        $this->assertSame('failed', $txOther->fresh()->status);
        $this->assertNull($txOther->fresh()->gateway_receipt);
    }
}
