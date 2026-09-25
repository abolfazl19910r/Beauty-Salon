<?php

namespace Tests\Feature\Payment;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\PaymentTransaction;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Notifications\Payment\PaymentRefundedNotification;
use App\Payments\Drivers\MellatDriver;
use App\Payments\GatewayManager;
use App\Payments\GatewayStartRequest;
use App\Payments\GatewayVerifyRequest;
use App\Payments\GatewayVerifyResult;
use App\Support\CurrentSalon;
use DOMDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Sleep;
use Tests\TestCase;

/**
 * ⭐ مرحله‌ی ۲ چند درگاه — درگاه مستقیم بانک ملت (به‌پرداخت، SOAP). پاسخ‌ها عین قالب وب‌سرویس به‌پرداخت
 * (<ns2:bpXxxResponse><return>…</return>) و پارامترهای مستند رسمی (نگارش ۱٫۱): شروع با فرم POST، بازگشت POST
 * بدون کوکی، verify → settle، و برگشت (bpReversalRequest) وقتی settle انجام نشد.
 */
class MellatGatewayTest extends TestCase
{
    use RefreshDatabase;

    private const CREDENTIALS = ['terminal_id' => '5012345', 'username' => 'salon_user', 'password' => 'p@ss<&>'];

    private Salon $salon;

    /** @var array<int, array{method: string, params: array<string, string>}> */
    private array $calls = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->salon = app(CurrentSalon::class)->get();
        $this->salon->paymentGateways()->delete();
        Sleep::fake();
    }

    private function driver(): MellatDriver
    {
        return new MellatDriver(self::CREDENTIALS);
    }

    private function tx(int $amountRial = 250000, string $token = 'AF82041a2Bf6989c7fF9'): PaymentTransaction
    {
        return PaymentTransaction::create([
            'salon_id' => $this->salon->id, 'driver' => 'mellat', 'purpose' => 'booking',
            'amount_rial' => $amountRial, 'token' => $token, 'callback_url' => 'https://salon.test/cb',
        ]);
    }

    /** پاسخ SOAP به‌پرداخت برای یک متد. */
    private static function soap(string $method, string $return): string
    {
        return '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body>'
            .'<ns2:'.$method.'Response xmlns:ns2="http://interfaces.core.sw.bps.com/"><return>'.$return.'</return></ns2:'.$method.'Response>'
            .'</soap:Body></soap:Envelope>';
    }

    /** نام متد و پارامترهای یک درخواست SOAP فرستاده‌شده. */
    private static function parse(Request $request): array
    {
        $dom = new DOMDocument;
        $dom->loadXML($request->body());
        $call = $dom->getElementsByTagNameNS(MellatDriver::SOAP_NAMESPACE, '*')->item(0);
        $params = [];
        foreach ($call->childNodes as $child) {
            $params[$child->nodeName] = $child->textContent;
        }

        return ['method' => $call->localName, 'params' => $params];
    }

    /**
     * بانک جعلی: هر متد → کد پاسخ ثابت، یا فهرستی به ترتیب، یا 'down' (قطع اتصال).
     *
     * @param  array<string, string|array<int, string>>  $returns
     */
    private function bank(array $returns): void
    {
        $this->calls = [];
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake([MellatDriver::SERVICE_URL => function (Request $request) use ($returns) {
            $call = self::parse($request);
            $this->calls[] = $call;
            $answer = $returns[$call['method']] ?? 'down';
            if (is_array($answer)) {
                $nth = count(array_filter($this->calls, fn ($c) => $c['method'] === $call['method'])) - 1;
                $answer = $answer[min($nth, count($answer) - 1)];
            }

            return $answer === 'down' ? Http::failedConnection() : Http::response(self::soap($call['method'], $answer), 200, ['Content-Type' => 'text/xml']);
        }]);
    }

    private function methods(): array
    {
        return array_column($this->calls, 'method');
    }

    /** بدنه‌ی POST بازگشت به‌پرداخت برای یک پرداخت موفق (جدول ۹ مستند + فیلدهای نسخه‌های جدیدتر). */
    private function mellatReturn(PaymentTransaction $tx, string $saleReferenceId = '5142510', array $override = []): array
    {
        return array_merge([
            'RefId' => $tx->token, 'ResCode' => '0', 'SaleOrderId' => (string) $tx->id, 'SaleReferenceId' => $saleReferenceId,
            'CardHolderInfo' => 'C9086F2AACF739F7D50ACC9FDC60C53E810FB9135723D256732CE4D1E2E409F7', 'CardHolderPan' => '610433******1234',
        ], $override);
    }

    private function verifyWith(PaymentTransaction $tx, array $callback): GatewayVerifyResult
    {
        return $this->driver()->verify(new GatewayVerifyRequest($tx->amount_rial, $callback, $tx->token, $tx->id));
    }

    // ── شروع پرداخت ──

    public function test_start_calls_bp_pay_request_in_rial_and_returns_a_post_form_to_startpay(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-09-26 06:30:05', 'UTC')); // ۱۰:۰۰:۰۵ تهران
        $this->bank(['bpPayRequest' => '0,AF82041a2Bf6989c7fF9']);

        $result = $this->driver()->start(new GatewayStartRequest(250000, 'https://salon.test/payments/return/abc?x=1&y=2', 'پیش پرداخت <نوبت>', '09121234567', null, '7', 42));

        $this->assertTrue($result->success);
        $this->assertSame('POST', $result->method);
        $this->assertSame('https://bpm.shaparak.ir/pgwchannel/startpay.mellat', $result->redirectUrl);
        $this->assertSame(['RefId' => 'AF82041a2Bf6989c7fF9'], $result->formFields);
        $this->assertSame('AF82041a2Bf6989c7fF9', $result->token);
        $this->assertSame(['bpPayRequest'], $this->methods());
        $this->assertSame([
            'terminalId' => '5012345', 'userName' => 'salon_user', 'userPassword' => 'p@ss<&>',
            'orderId' => '42', 'amount' => '250000', 'localDate' => '20260926', 'localTime' => '100005',
            'additionalData' => 'پیش پرداخت <نوبت>', 'callBackUrl' => 'https://salon.test/payments/return/abc?x=1&y=2', 'payerId' => '0',
        ], $this->calls[0]['params'], 'کاراکترهای ویژه‌ی XML سالم می‌رسن');
        Http::assertSent(fn (Request $r) => $r->url() === MellatDriver::SERVICE_URL && str_starts_with($r->header('Content-Type')[0], 'text/xml'));
    }

    public function test_pay_request_errors_map_to_messages_and_only_real_outages_fail_over(): void
    {
        $start = fn () => $this->driver()->start(new GatewayStartRequest(250000, 'https://salon.test/r', 'x', transactionId: 1));

        $this->bank(['bpPayRequest' => '421']);
        $ip = $start();
        $this->assertFalse($ip->success);
        $this->assertFalse($ip->retryable, 'IP ثبت‌نشده خطای پیکربندیه، نه قطعی');
        $this->assertStringContainsString('IP سرور', $ip->message);

        $this->bank(['bpPayRequest' => '24']);
        $this->assertStringContainsString('نام کاربری یا رمز', $start()->message);
        $this->assertFalse($start()->retryable);

        $this->bank(['bpPayRequest' => '34']);
        $this->assertTrue($start()->retryable, 'خطای سیستمی بانک = درگاه در دسترس نیست');

        $this->bank(['bpPayRequest' => 'down']);
        $this->assertTrue($start()->retryable);

        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake([MellatDriver::SERVICE_URL => Http::response('<html>Service Unavailable</html>', 503)]);
        $this->assertTrue($start()->retryable);

        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake([MellatDriver::SERVICE_URL => Http::response('<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body><soap:Fault><faultcode>soap:Client</faultcode><faultstring>Unmarshalling Error</faultstring></soap:Fault></soap:Body></soap:Envelope>', 500)]);
        $fault = $start();
        $this->assertFalse($fault->success);
        $this->assertFalse($fault->retryable, 'SOAP Fault یعنی درخواست ما ایراد داره، نه اینکه بانک قطعه');
    }

    // ── تایید و واریز ──

    public function test_verify_then_settle_with_our_own_order_id_and_the_bank_reference(): void
    {
        $tx = $this->tx();
        $this->bank(['bpVerifyRequest' => '0', 'bpSettleRequest' => '0']);

        $result = $this->verifyWith($tx, $this->mellatReturn($tx));

        $this->assertTrue($result->success);
        $this->assertSame('5142510', $result->refId);
        $this->assertSame('610433******1234', $result->cardPan);
        $this->assertTrue($result->raw['settled']);
        $this->assertSame('5142510', $tx->fresh()->gateway_receipt);
        $this->assertSame(['bpVerifyRequest', 'bpSettleRequest'], $this->methods());
        foreach ($this->calls as $call) {
            $this->assertSame(['terminalId' => '5012345', 'userName' => 'salon_user', 'userPassword' => 'p@ss<&>',
                'orderId' => (string) $tx->id, 'saleOrderId' => (string) $tx->id, 'saleReferenceId' => '5142510'], $call['params']);
        }
    }

    public function test_a_cancelled_or_failed_return_is_never_verified(): void
    {
        $tx = $this->tx();
        $this->bank([]);

        $cancelled = $this->verifyWith($tx, $this->mellatReturn($tx, '', ['ResCode' => '17']));
        $this->assertFalse($cancelled->success);
        $this->assertTrue($cancelled->cancelledByUser);

        $noFunds = $this->verifyWith($tx, $this->mellatReturn($tx, '', ['ResCode' => '12']));
        $this->assertFalse($noFunds->success);
        $this->assertFalse($noFunds->cancelledByUser);
        $this->assertStringContainsString('موجودی', $noFunds->message);

        $this->assertFalse($this->verifyWith($tx, $this->mellatReturn($tx, '', ['ResCode' => null]))->success);

        $this->assertSame([], $this->calls);
        $this->assertNull($tx->fresh()->gateway_receipt);
    }

    public function test_a_return_belonging_to_another_transaction_is_rejected_without_calling_the_bank(): void
    {
        $tx = $this->tx();
        $this->bank(['bpVerifyRequest' => '0', 'bpSettleRequest' => '0']);

        $this->assertFalse($this->verifyWith($tx, $this->mellatReturn($tx, '1', ['SaleOrderId' => '999999']))->success);
        $this->assertFalse($this->verifyWith($tx, $this->mellatReturn($tx, '1', ['RefId' => 'SOMEONE-ELSES']))->success);
        $this->assertFalse($this->verifyWith($tx, $this->mellatReturn($tx, 'not-a-number'))->success);

        $this->assertSame([], $this->calls);
    }

    public function test_a_reference_already_used_by_another_transaction_is_refused_before_verify(): void
    {
        $first = $this->tx(250000, 'R1');
        $second = $this->tx(250000, 'R2');
        $this->bank(['bpVerifyRequest' => '0', 'bpSettleRequest' => '0']);

        $this->assertTrue($this->verifyWith($first, $this->mellatReturn($first, '777'))->success);

        $this->bank(['bpVerifyRequest' => '0', 'bpSettleRequest' => '0']);
        $replay = $this->verifyWith($second, $this->mellatReturn($second, '777'));

        $this->assertFalse($replay->success);
        $this->assertStringContainsString('قبلاً', $replay->message);
        $this->assertSame([], $this->calls);
        $this->assertNull($second->fresh()->gateway_receipt);
    }

    public function test_a_retry_for_the_same_transaction_accepts_already_verified_and_already_settled(): void
    {
        $tx = $this->tx();
        $this->bank(['bpVerifyRequest' => '43', 'bpSettleRequest' => '45']);

        $this->assertTrue($this->verifyWith($tx, $this->mellatReturn($tx))->success, 'پاسخ قبلی گم شده و دوباره تایید می‌کنیم');
    }

    public function test_verify_error_codes_fail_without_settling_or_reversing(): void
    {
        $tx = $this->tx();
        $this->bank(['bpVerifyRequest' => '421']);

        $result = $this->verifyWith($tx, $this->mellatReturn($tx));

        $this->assertFalse($result->success);
        $this->assertStringContainsString('IP سرور', $result->message);
        $this->assertArrayNotHasKey('unanswered', $result->raw);
        $this->assertSame(['bpVerifyRequest'], $this->methods(), 'پاسخ منفی = verify نشده؛ به‌پرداخت خودش برگشت می‌زنه');
    }

    public function test_an_unanswered_verify_is_retried_then_marked_for_reconcile(): void
    {
        $tx = $this->tx();
        $this->bank(['bpVerifyRequest' => 'down']);

        $result = $this->verifyWith($tx, $this->mellatReturn($tx));

        $this->assertFalse($result->success);
        $this->assertTrue($result->raw['unanswered']);
        $this->assertSame(['bpVerifyRequest', 'bpVerifyRequest', 'bpVerifyRequest'], $this->methods());

        $this->bank(['bpVerifyRequest' => ['down', '0'], 'bpSettleRequest' => '0']);
        $this->assertTrue($this->verifyWith($tx, $this->mellatReturn($tx))->success, 'refresh مشتری در همون مهلت');
    }

    public function test_a_failed_settle_is_reversed_to_the_card_and_rejected(): void
    {
        $tx = $this->tx();
        $this->bank(['bpVerifyRequest' => '0', 'bpSettleRequest' => '61', 'bpReversalRequest' => '0']);

        $result = $this->verifyWith($tx, $this->mellatReturn($tx));

        $this->assertFalse($result->success);
        $this->assertTrue($result->raw['reversed']);
        $this->assertSame('settle_failed', $result->raw['refund_reason']);
        $this->assertStringContainsString('برگشت داده شد', $result->message);
        $this->assertSame(['bpVerifyRequest', 'bpSettleRequest', 'bpReversalRequest'], $this->methods());
        $this->assertSame((string) $tx->id, $this->calls[2]['params']['saleOrderId']);
        $this->assertSame('5142510', $this->calls[2]['params']['saleReferenceId']);
    }

    public function test_an_unanswered_settle_that_had_actually_gone_through_is_a_success(): void
    {
        $tx = $this->tx();
        $this->bank(['bpVerifyRequest' => '0', 'bpSettleRequest' => 'down', 'bpReversalRequest' => '45']);

        $result = $this->verifyWith($tx, $this->mellatReturn($tx));

        $this->assertTrue($result->success, 'برگشت گفت «قبلاً settle شده» — پول به حساب سالن رسیده');
        $this->assertSame('45', $result->raw['settle']);
    }

    public function test_when_neither_settle_nor_reversal_answers_the_payment_is_left_for_reconcile(): void
    {
        $tx = $this->tx();
        $this->bank(['bpVerifyRequest' => '0', 'bpSettleRequest' => 'down', 'bpReversalRequest' => 'down']);

        $result = $this->verifyWith($tx, $this->mellatReturn($tx));

        $this->assertFalse($result->success);
        $this->assertTrue($result->raw['unanswered']);
        $this->assertArrayNotHasKey('reversed', $result->raw);
    }

    public function test_reverse_transaction_only_reverses_unsettled_payments(): void
    {
        Log::spy();
        $settled = $this->tx();
        $settled->update(['gateway_receipt' => '11', 'status' => 'paid', 'verify_response' => ['settled' => true]]);
        $open = $this->tx();
        $open->update(['gateway_receipt' => '22', 'status' => 'failed', 'verify_response' => ['unanswered' => true]]);

        $this->bank(['bpReversalRequest' => '48']);
        $this->assertFalse($this->driver()->reverseTransaction($settled->fresh()), 'settle‌شده برگشت‌پذیر نیست → کیف پول');
        $this->assertSame([], $this->calls);
        $this->assertTrue($this->driver()->reverseTransaction($open->fresh()), '۴۸ = قبلاً برگشت خورده');

        $this->bank(['bpReversalRequest' => '45']);
        $this->assertFalse($this->driver()->reverseTransaction($open->fresh()));
        Log::shouldHaveReceived('error')->withArgs(fn ($message) => str_contains($message, 'settled'))->once();
    }

    // ── کاتالوگ و صفحه‌ی مدیریت ──

    public function test_owner_can_add_mellat_and_the_password_is_never_shown_again(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);

        $this->actingAs($owner)->get(route('admin.payment-gateways.index'))->assertOk()->assertSee('بانک ملت (به‌پرداخت)');
        $this->actingAs($owner)->post(route('admin.payment-gateways.store'), [
            'driver' => 'mellat', 'credentials' => ['terminal_id' => 'ABC', 'username' => 'u', 'password' => 'p'],
        ])->assertSessionHasErrors('credentials.terminal_id');
        $this->actingAs($owner)->post(route('admin.payment-gateways.store'), [
            'driver' => 'mellat', 'credentials' => ['terminal_id' => '۵۰۱۲۳۴۵', 'username' => 'salon_user', 'password' => 'SECRET-PASS-99'], 'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $gateway = $this->salon->paymentGateways()->where('driver', 'mellat')->sole();
        $this->assertSame(['terminal_id' => '5012345', 'username' => 'salon_user', 'password' => 'SECRET-PASS-99'], $gateway->credentials);
        $this->assertInstanceOf(MellatDriver::class, app(GatewayManager::class)->driver($gateway));
        $this->actingAs($owner)->get(route('admin.payment-gateways.index'))->assertDontSee('SECRET-PASS-99');
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

    /** مشتری به فرم POST به‌پرداخت فرستاده می‌شه؛ تراکنش ساخته‌شده برمی‌گرده. */
    private function sendToBank(User $user, Booking $booking): PaymentTransaction
    {
        $this->actingAs($user)->post(route('payment.process', $booking))
            ->assertOk()
            ->assertSee('action="https://bpm.shaparak.ir/pgwchannel/startpay.mellat"', false)
            ->assertSee('name="RefId" value="REF-'.$booking->id.'"', false);

        return PaymentTransaction::where('payable_id', $booking->id)->sole();
    }

    /** به‌پرداخت با POST cross-site و بدون کوکی برمی‌گرده → 303؛ مرورگر با GET (کوکی Lax) به callback نوبت. */
    private function comeBackFromBank(User $user, PaymentTransaction $tx, array $post): \Illuminate\Testing\TestResponse
    {
        $this->app['auth']->forgetGuards();
        $bounce = $this->post("/payments/return/{$tx->public_id}", $post);
        $bounce->assertStatus(303);

        return $this->actingAs($user)->get($bounce->headers->get('Location'));
    }

    public function test_full_booking_payment_with_the_post_form_and_the_cookieless_post_return(): void
    {
        $this->salon->paymentGateways()->create(['driver' => 'mellat', 'credentials' => self::CREDENTIALS, 'priority' => 1, 'fee_fixed_toman' => 500]);
        $user = User::factory()->create(['phone' => '09121234567']);
        $booking = $this->booking($user);
        $this->bank(['bpPayRequest' => '0,REF-'.$booking->id, 'bpVerifyRequest' => '0', 'bpSettleRequest' => '0']);

        $tx = $this->sendToBank($user, $booking);
        $this->assertSame('mellat', $tx->driver);
        $this->assertSame(605000, $tx->amount_rial, '۶۰٬۰۰۰ + ۵۰۰ تومان کارمزد');
        $this->assertSame((string) $tx->id, $this->calls[0]['params']['orderId']);
        $this->assertSame('605000', $this->calls[0]['params']['amount']);
        $this->assertSame(route('payments.return', ['publicId' => $tx->public_id]), $this->calls[0]['params']['callBackUrl']);

        $this->comeBackFromBank($user, $tx, $this->mellatReturn($tx, '98765432'));

        $booking->refresh();
        $this->assertSame('paid', $booking->payment_status);
        $this->assertSame('mellat', $booking->payment_details['gateway']);
        $this->assertSame(500, $booking->payment_details['gateway_fee']);
        $tx->refresh();
        $this->assertSame('paid', $tx->status);
        $this->assertSame('98765432', $tx->ref_id);
        $this->assertSame('98765432', $tx->gateway_receipt);
        $this->assertSame('610433******1234', $tx->card_pan);
        $this->assertSame(['bpPayRequest', 'bpVerifyRequest', 'bpSettleRequest'], $this->methods());

        // callback تکراری دوباره verify/settle نمی‌زنه و نوبت دوباره ثبت نمی‌شه
        $this->comeBackFromBank($user, $tx, $this->mellatReturn($tx, '98765432'));
        $this->assertCount(3, $this->calls);
    }

    public function test_a_booking_whose_settle_failed_is_not_paid_and_the_customer_is_told_by_sms(): void
    {
        Notification::fake();
        $this->salon->paymentGateways()->create(['driver' => 'mellat', 'credentials' => self::CREDENTIALS, 'priority' => 1]);
        $user = User::factory()->create(['phone' => '09121234567']);
        $booking = $this->booking($user);
        $this->bank(['bpPayRequest' => '0,REF-'.$booking->id, 'bpVerifyRequest' => '0', 'bpSettleRequest' => '61', 'bpReversalRequest' => '0']);

        $tx = $this->sendToBank($user, $booking);
        $this->comeBackFromBank($user, $tx, $this->mellatReturn($tx, '55555'));

        $this->assertNotSame('paid', $booking->fresh()->payment_status);
        $this->assertTrue($tx->fresh()->verify_response['reversed']);
        Notification::assertSentTo($user, PaymentRefundedNotification::class, fn ($n) => $n->reason === 'settle_failed'
            && $n->cardToman === 60000 && str_contains($n->text, 'واریز نهایی'));
    }

    public function test_a_used_reference_cannot_pay_a_second_booking(): void
    {
        $this->salon->paymentGateways()->create(['driver' => 'mellat', 'credentials' => self::CREDENTIALS, 'priority' => 1]);
        $user = User::factory()->create();
        $paid = $this->booking($user);
        $other = $this->booking($user);
        $this->bank(['bpPayRequest' => ['0,REF-'.$paid->id, '0,REF-'.$other->id], 'bpVerifyRequest' => '0', 'bpSettleRequest' => '0']);

        $txPaid = $this->sendToBank($user, $paid);
        $txOther = $this->sendToBank($user, $other);

        $this->comeBackFromBank($user, $txPaid, $this->mellatReturn($txPaid, '4444'));
        $this->assertSame('paid', $paid->fresh()->payment_status);

        // همون مرجع با RefId و شماره‌ی سفارش نوبت دوم
        $this->comeBackFromBank($user, $txOther, $this->mellatReturn($txOther, '4444'));

        $this->assertNotSame('paid', $other->fresh()->payment_status);
        $this->assertSame('failed', $txOther->fresh()->status);
        $this->assertNull($txOther->fresh()->gateway_receipt);
        $this->assertSame(['bpPayRequest', 'bpPayRequest', 'bpVerifyRequest', 'bpSettleRequest'], $this->methods());
    }
}
