<?php

namespace Tests\Feature\Payment;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\PaymentTransaction;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Notifications\Payment\PaymentRefundedNotification;
use App\Payments\Drivers\ParsianDriver;
use App\Payments\GatewayManager;
use App\Payments\GatewayReceipt;
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
 * ⭐ مرحله‌ی ۲ چند درگاه — درگاه مستقیم بانک پارسیان (تجارت الکترونیک پارسیان، SOAP). پاسخ‌ها عین قالب وب‌سرویس ASMX
 * (<{Method}Response><{Method}Result>…) با SOAP 1.1 و SOAPAction صفحه‌ی راهنمای خود سرویس؛ برگشت با SOAP 1.2.
 */
class ParsianGatewayTest extends TestCase
{
    use RefreshDatabase;

    private const CREDENTIALS = ['pin' => 'PIN<&>123', 'terminal_id' => '44012345'];

    private Salon $salon;

    /** @var array<int, array{method: string, params: array<string, string>, request: Request}> */
    private array $calls = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->salon = app(CurrentSalon::class)->get();
        $this->salon->paymentGateways()->delete();
        Sleep::fake();
    }

    private function driver(array $credentials = self::CREDENTIALS): ParsianDriver
    {
        return new ParsianDriver($credentials);
    }

    private function tx(int $amountRial = 250000, string $token = '1234567890'): PaymentTransaction
    {
        return PaymentTransaction::create([
            'salon_id' => $this->salon->id, 'driver' => 'parsian', 'purpose' => 'booking',
            'amount_rial' => $amountRial, 'token' => $token, 'callback_url' => 'https://salon.test/cb',
        ]);
    }

    private static function response(string $method, array $result): string
    {
        $fields = '';
        foreach ($result as $k => $v) {
            $fields .= "<{$k}>{$v}</{$k}>";
        }

        return '<?xml version="1.0" encoding="utf-8"?><soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body>'
            ."<{$method}Response xmlns=\"https://pec.Shaparak.ir/NewIPGServices\"><{$method}Result>{$fields}</{$method}Result></{$method}Response>"
            .'</soap:Body></soap:Envelope>';
    }

    private static function parse(Request $request): array
    {
        $dom = new DOMDocument;
        $dom->loadXML($request->body());
        $body = $dom->getElementsByTagNameNS('*', 'Body')->item(0);
        $call = null;
        foreach ($body->childNodes as $n) {
            if ($n->nodeType === XML_ELEMENT_NODE) {
                $call = $n;
            }
        }
        $params = [];
        foreach ($call->getElementsByTagName('requestData')->item(0)->childNodes as $child) {
            $params[$child->nodeName] = $child->textContent;
        }

        return ['method' => $call->localName, 'ns' => $call->namespaceURI, 'params' => $params, 'request' => $request];
    }

    /**
     * بانک جعلی: متد → فیلدهای نتیجه، فهرستی به ترتیب، یا 'down'.
     *
     * @param  array<string, array|string>  $returns
     */
    private function bank(array $returns): void
    {
        $this->calls = [];
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['pec.shaparak.ir/*' => function (Request $request) use ($returns) {
            $call = self::parse($request);
            $this->calls[] = $call;
            $answer = $returns[$call['method']] ?? 'down';
            if (is_array($answer) && array_is_list($answer)) {
                $nth = count(array_filter($this->calls, fn ($c) => $c['method'] === $call['method'])) - 1;
                $answer = $answer[min($nth, count($answer) - 1)];
            }

            return $answer === 'down' ? Http::failedConnection() : Http::response(self::response($call['method'], $answer), 200, ['Content-Type' => 'text/xml']);
        }]);
    }

    private function methods(): array
    {
        return array_column($this->calls, 'method');
    }

    /** بدنه‌ی POST بازگشت پارسیان برای یک پرداخت موفق. */
    private function parsianReturn(PaymentTransaction $tx, array $override = []): array
    {
        return array_merge([
            'Token' => $tx->token, 'status' => '0', 'OrderId' => (string) $tx->id, 'TerminalNo' => '44012345',
            'Amount' => (string) $tx->amount_rial, 'RRN' => '731234567890', 'HashCardNumber' => 'A1B2C3',
        ], $override);
    }

    private function verifyWith(PaymentTransaction $tx, array $callback): GatewayVerifyResult
    {
        return $this->driver()->verify(new GatewayVerifyRequest($tx->amount_rial, $callback, $tx->token, $tx->id));
    }

    // ── شروع پرداخت ──

    public function test_start_sends_sale_payment_request_and_redirects_to_the_payment_page(): void
    {
        $this->bank(['SalePaymentRequest' => ['Token' => '9876543210', 'Status' => '0', 'Message' => '']]);

        $result = $this->driver()->start(new GatewayStartRequest(250000, 'https://salon.test/payments/return/abc?x=1&y=2', 'پیش پرداخت <نوبت>', '09121234567', null, '7', 42));

        $this->assertTrue($result->success);
        $this->assertSame('GET', $result->method);
        $this->assertSame('https://pec.shaparak.ir/NewIPG/?Token=9876543210', $result->redirectUrl);
        $this->assertSame('9876543210', $result->token);
        $this->assertSame(['SalePaymentRequest'], $this->methods());
        $this->assertSame(ParsianDriver::SALE_NS, $this->calls[0]['ns']);
        $this->assertSame([
            'LoginAccount' => 'PIN<&>123', 'Amount' => '250000', 'OrderId' => '42',
            'CallBackUrl' => 'https://salon.test/payments/return/abc?x=1&y=2', 'AdditionalData' => 'پیش پرداخت <نوبت>',
        ], $this->calls[0]['params'], 'کاراکترهای ویژه‌ی XML سالم می‌رسن');

        $sent = $this->calls[0]['request'];
        $this->assertSame(ParsianDriver::SALE_URL, $sent->url());
        $this->assertSame('"https://pec.Shaparak.ir/NewIPGServices/Sale/SaleService/SalePaymentRequest"', $sent->header('SOAPAction')[0], 'SOAPAction صفحه‌ی راهنمای خود سرویس');
        $this->assertStringStartsWith('text/xml', $sent->header('Content-Type')[0]);
    }

    public function test_sale_request_errors_map_to_messages_and_only_outages_fail_over(): void
    {
        $start = fn () => $this->driver()->start(new GatewayStartRequest(250000, 'https://salon.test/r', 'x', transactionId: 1));

        $this->bank(['SalePaymentRequest' => ['Token' => '0', 'Status' => '-126', 'Message' => 'x']]);
        $pin = $start();
        $this->assertFalse($pin->success);
        $this->assertFalse($pin->retryable);
        $this->assertStringContainsString('PIN', $pin->message);

        $this->bank(['SalePaymentRequest' => ['Token' => '0', 'Status' => '-1']]);
        $this->assertTrue($start()->retryable, 'خطای سرور بانک');

        $this->bank(['SalePaymentRequest' => 'down']);
        $this->assertTrue($start()->retryable);

        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['pec.shaparak.ir/*' => Http::response('<html>Service Unavailable</html>', 503)]);
        $this->assertTrue($start()->retryable);

        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['pec.shaparak.ir/*' => Http::response('<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body><soap:Fault><faultcode>soap:Client</faultcode><faultstring>Server did not recognize the value of HTTP Header SOAPAction</faultstring></soap:Fault></soap:Body></soap:Envelope>', 500)]);
        $fault = $start();
        $this->assertFalse($fault->success);
        $this->assertFalse($fault->retryable, 'SOAP Fault یعنی درخواست ما ایراد داره');
    }

    // ── تایید ──

    public function test_confirm_uses_our_stored_token_and_records_the_rrn(): void
    {
        $tx = $this->tx();
        $this->bank(['ConfirmPayment' => ['Status' => '0', 'RRN' => '731234567890', 'CardNumberMasked' => '622106******1234', 'Token' => $tx->token]]);

        $result = $this->verifyWith($tx, $this->parsianReturn($tx));

        $this->assertTrue($result->success);
        $this->assertSame('731234567890', $result->refId);
        $this->assertSame('622106******1234', $result->cardPan);
        $this->assertSame($tx->token, $tx->fresh()->gateway_receipt);
        $this->assertSame(['ConfirmPayment'], $this->methods());
        $this->assertSame(['LoginAccount' => 'PIN<&>123', 'Token' => $tx->token], $this->calls[0]['params']);
        $this->assertSame(ParsianDriver::CONFIRM_URL, $this->calls[0]['request']->url());
        $this->assertSame('"https://pec.Shaparak.ir/NewIPGServices/Confirm/ConfirmService/ConfirmPayment"', $this->calls[0]['request']->header('SOAPAction')[0]);
    }

    public function test_callback_keys_are_read_case_insensitively(): void
    {
        $tx = $this->tx();
        $this->bank(['ConfirmPayment' => ['Status' => '0', 'RRN' => '5555']]);

        $this->assertTrue($this->verifyWith($tx, ['token' => $tx->token, 'Status' => '0', 'orderId' => (string) $tx->id, 'amount' => '250,000'])->success);
    }

    public function test_a_return_without_a_token_field_is_still_confirmed_with_our_stored_token(): void
    {
        $tx = $this->tx();
        $this->bank(['ConfirmPayment' => ['Status' => '0', 'RRN' => '5555']]);

        $this->assertTrue($this->verifyWith($tx, ['status' => '0', 'OrderId' => (string) $tx->id])->success);
        $this->assertSame($tx->token, $this->calls[0]['params']['Token']);
    }

    public function test_a_cancelled_or_failed_return_is_never_confirmed(): void
    {
        $tx = $this->tx();
        $this->bank([]);

        $cancelled = $this->verifyWith($tx, $this->parsianReturn($tx, ['status' => '-138']));
        $this->assertFalse($cancelled->success);
        $this->assertTrue($cancelled->cancelledByUser);

        $noFunds = $this->verifyWith($tx, $this->parsianReturn($tx, ['status' => '51']));
        $this->assertFalse($noFunds->cancelledByUser);
        $this->assertStringContainsString('موجودی', $noFunds->message);

        $this->assertFalse($this->verifyWith($tx, $this->parsianReturn($tx, ['status' => '']))->success);
        $this->assertSame([], $this->calls);
        $this->assertNull($tx->fresh()->gateway_receipt);
    }

    public function test_a_return_that_does_not_match_this_transaction_is_rejected_without_calling_the_bank(): void
    {
        $tx = $this->tx();
        $this->bank(['ConfirmPayment' => ['Status' => '0', 'RRN' => '1']]);

        foreach ([['Token' => '999'], ['OrderId' => '777777'], ['Amount' => '10'], ['TerminalNo' => '11111111']] as $forged) {
            $result = $this->verifyWith($tx, $this->parsianReturn($tx, $forged));
            $this->assertFalse($result->success, json_encode($forged));
            $this->assertStringContainsString('همخوانی ندارد', $result->message);
        }

        $this->assertSame([], $this->calls);
    }

    public function test_a_token_already_claimed_by_another_transaction_is_refused(): void
    {
        $first = $this->tx(250000, '555');
        $this->assertTrue(GatewayReceipt::claim('parsian', '555', $first->id));
        $second = $this->tx(250000, '555');
        $this->bank(['ConfirmPayment' => ['Status' => '0', 'RRN' => '1']]);

        $this->assertFalse($this->verifyWith($second, $this->parsianReturn($second))->success);
        $this->assertSame([], $this->calls);
    }

    public function test_a_refresh_after_a_lost_confirm_answer_uses_the_banks_rrn(): void
    {
        $tx = $this->tx();
        $this->bank(['ConfirmPayment' => ['Status' => '-1533', 'RRN' => '0']]);

        $result = $this->verifyWith($tx, $this->parsianReturn($tx, ['RRN' => '731234567890']));
        $this->assertTrue($result->success);
        $this->assertSame('731234567890', $result->refId);
        $this->assertTrue($result->raw['already_confirmed']);

        $this->assertFalse($this->verifyWith($tx, $this->parsianReturn($tx, ['RRN' => '']))->success, 'بدون RRN چیزی برای ثبت نیست');
    }

    public function test_confirm_errors_fail_and_an_unanswered_confirm_is_left_for_reconcile(): void
    {
        $tx = $this->tx();
        $this->bank(['ConfirmPayment' => ['Status' => '-1540', 'RRN' => '0']]);
        $failed = $this->verifyWith($tx, $this->parsianReturn($tx));
        $this->assertFalse($failed->success);
        $this->assertArrayNotHasKey('unanswered', $failed->raw);

        $this->bank(['ConfirmPayment' => 'down']);
        $lost = $this->verifyWith($tx, $this->parsianReturn($tx));
        $this->assertFalse($lost->success);
        $this->assertTrue($lost->raw['unanswered']);
        $this->assertSame(['ConfirmPayment', 'ConfirmPayment', 'ConfirmPayment'], $this->methods());
    }

    // ── برگشت ──

    public function test_reverse_transaction_uses_soap_12_and_accepts_already_reversed(): void
    {
        Log::spy();
        $tx = $this->tx();
        $tx->update(['gateway_receipt' => $tx->token, 'status' => 'paid']);

        $this->bank(['ReversalRequest' => ['Status' => '0', 'Message' => '']]);
        $this->assertTrue($this->driver()->reverseTransaction($tx->fresh()));
        $sent = $this->calls[0]['request'];
        $this->assertSame(ParsianDriver::REVERSAL_URL, $sent->url());
        $this->assertSame(ParsianDriver::REVERSAL_NS, $this->calls[0]['ns']);
        $this->assertStringStartsWith('application/soap+xml', $sent->header('Content-Type')[0]);
        $this->assertStringContainsString('action="https://pec.Shaparak.ir/NewIPGServices/Reversal/ReversalService/ReversalRequest"', $sent->header('Content-Type')[0]);

        $this->bank(['ReversalRequest' => ['Status' => '-1551']]);
        $this->assertTrue($this->driver()->reverseTransaction($tx->fresh()));

        $this->bank(['ReversalRequest' => ['Status' => '-1549']]);
        $this->assertFalse($this->driver()->reverseTransaction($tx->fresh()));
        Log::shouldHaveReceived('warning')->withArgs(fn ($m) => str_contains($m, 'window'))->once();

        $other = $this->tx();
        $other->update(['driver' => 'saman', 'gateway_receipt' => '1']);
        $this->bank([]);
        $this->assertFalse($this->driver()->reverseTransaction($other->fresh()));
        $this->assertSame([], $this->calls);
    }

    // ── مدیریت ──

    public function test_owner_can_add_parsian_and_the_pin_is_never_shown_again(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);

        $this->actingAs($owner)->get(route('admin.payment-gateways.index'))->assertOk()->assertSee('بانک پارسیان (تاپ)');
        $this->actingAs($owner)->post(route('admin.payment-gateways.store'), [
            'driver' => 'parsian', 'credentials' => ['pin' => 'SECRET-PIN-77', 'terminal_id' => '۴۴۰۱۲۳۴۵'], 'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $gateway = $this->salon->paymentGateways()->where('driver', 'parsian')->sole();
        $this->assertSame(['pin' => 'SECRET-PIN-77', 'terminal_id' => '44012345'], $gateway->credentials);
        $this->assertInstanceOf(ParsianDriver::class, app(GatewayManager::class)->driver($gateway));
        $this->actingAs($owner)->get(route('admin.payment-gateways.index'))->assertDontSee('SECRET-PIN-77');
    }

    // ── مسیر کامل پرداخت نوبت ──

    private function booking(User $user, array $attributes = []): Booking
    {
        return Booking::factory()->create($attributes + [
            'user_id' => $user->id,
            'service_id' => BeautyService::factory()->create(['price' => 200000])->id,
            'specialist_id' => Specialist::factory()->create()->id,
            'payment_status' => 'unpaid', 'status' => 'pending_payment', 'prepayment_amount' => 60000,
        ]);
    }

    private function comeBackFromBank(User $user, PaymentTransaction $tx, array $post): \Illuminate\Testing\TestResponse
    {
        $this->app['auth']->forgetGuards();
        $bounce = $this->post("/payments/return/{$tx->public_id}", $post);
        $bounce->assertStatus(303);

        return $this->actingAs($user)->get($bounce->headers->get('Location'));
    }

    public function test_full_booking_payment_with_a_redirect_and_the_cookieless_post_return(): void
    {
        $this->salon->paymentGateways()->create(['driver' => 'parsian', 'credentials' => self::CREDENTIALS, 'priority' => 1, 'fee_fixed_toman' => 500]);
        $user = User::factory()->create(['phone' => '09121234567']);
        $booking = $this->booking($user);
        $this->bank(['SalePaymentRequest' => ['Token' => '8000'.$booking->id, 'Status' => '0'], 'ConfirmPayment' => ['Status' => '0', 'RRN' => '731000000001', 'CardNumberMasked' => '622106******9999']]);

        $this->actingAs($user)->post(route('payment.process', $booking))->assertRedirect('https://pec.shaparak.ir/NewIPG/?Token=8000'.$booking->id);
        $tx = PaymentTransaction::where('payable_id', $booking->id)->sole();
        $this->assertSame('parsian', $tx->driver);
        $this->assertSame(605000, $tx->amount_rial);
        $this->assertSame((string) $tx->id, $this->calls[0]['params']['OrderId']);
        $this->assertSame(route('payments.return', ['publicId' => $tx->public_id]), $this->calls[0]['params']['CallBackUrl']);

        $this->comeBackFromBank($user, $tx, $this->parsianReturn($tx));

        $this->assertSame('paid', $booking->fresh()->payment_status);
        $tx->refresh();
        $this->assertSame('paid', $tx->status);
        $this->assertSame('731000000001', $tx->ref_id);
        $this->assertSame('622106******9999', $tx->card_pan);

        $this->comeBackFromBank($user, $tx, $this->parsianReturn($tx));
        $this->assertSame(['SalePaymentRequest', 'ConfirmPayment'], $this->methods(), 'callback تکراری دوباره تایید نمی‌زنه');
    }

    public function test_a_parsian_payment_for_a_lost_slot_goes_back_to_the_card(): void
    {
        Notification::fake();
        $this->salon->paymentGateways()->create(['driver' => 'parsian', 'credentials' => self::CREDENTIALS, 'priority' => 1]);
        $specialist = Specialist::factory()->create(['auto_confirm_bookings' => true]);
        $service = BeautyService::factory()->create(['price' => 200000]);
        $time = now()->addDays(2)->setTime(10, 0);
        $user = User::factory()->create(['phone' => '09121234567']);
        $booking = $this->booking($user, ['service_id' => $service->id, 'specialist_id' => $specialist->id, 'booking_time' => $time]);
        $this->bank(['SalePaymentRequest' => ['Token' => '42424242', 'Status' => '0'], 'ConfirmPayment' => ['Status' => '0', 'RRN' => '99'], 'ReversalRequest' => ['Status' => '0']]);

        $this->actingAs($user)->post(route('payment.process', $booking));
        $tx = PaymentTransaction::where('payable_id', $booking->id)->sole();

        $booking->update(['status' => 'cancelled', 'cancelled_by' => 'admin', 'cancelled_at' => now()]);
        Booking::factory()->create([
            'user_id' => User::factory()->create()->id, 'service_id' => $service->id, 'specialist_id' => $specialist->id,
            'booking_time' => $time, 'payment_status' => 'paid', 'status' => 'confirmed', 'prepayment_amount' => 60000,
        ]);

        $this->comeBackFromBank($user, $tx, $this->parsianReturn($tx));

        $this->assertSame('reversed', $tx->fresh()->status);
        $this->assertSame(['SalePaymentRequest', 'ConfirmPayment', 'ReversalRequest'], $this->methods());
        $this->assertSame(0.0, (float) $user->getOrCreateWallet()->fresh()->balance, 'به کارت برگشت، نه کیف پول');
        Notification::assertSentTo($user, PaymentRefundedNotification::class, fn ($n) => $n->reason === 'slot_taken' && $n->cardToman === 60000);
    }

    public function test_an_unanswered_confirm_is_reversed_by_reconcile_inside_the_parsian_window(): void
    {
        Notification::fake();
        $gateway = $this->salon->paymentGateways()->create(['driver' => 'parsian', 'credentials' => self::CREDENTIALS, 'priority' => 1]);
        $user = User::factory()->create(['phone' => '09121234567']);
        $tx = $this->tx();
        $tx->update(['gateway_id' => $gateway->id, 'user_id' => $user->id]);
        $this->bank(['ConfirmPayment' => 'down']);
        $result = $this->verifyWith($tx, $this->parsianReturn($tx));
        $tx->update(['status' => 'failed', 'verify_response' => $result->raw]);

        $this->bank(['ReversalRequest' => ['Status' => '0']]);
        $this->travel(10)->minutes();
        $this->artisan('payments:reconcile');
        $this->assertSame('failed', $tx->fresh()->status, 'هنوز refresh مشتری ممکنه');
        $this->assertSame([], $this->calls);

        $this->travel(15)->minutes();
        $this->artisan('payments:reconcile');
        $this->assertSame('reversed', $tx->fresh()->status);
        $this->assertSame(['ReversalRequest'], $this->methods());
        Notification::assertSentTo($user, PaymentRefundedNotification::class, fn ($n) => $n->reason === 'verify_unanswered');
    }
}
