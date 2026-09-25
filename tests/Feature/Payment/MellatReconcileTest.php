<?php

namespace Tests\Feature\Payment;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\PaymentTransaction;
use App\Models\Salon;
use App\Models\SalonPaymentGateway;
use App\Models\Specialist;
use App\Models\User;
use App\Notifications\Payment\PaymentRefundedNotification;
use App\Payments\Drivers\MellatDriver;
use App\Payments\GatewayReceipt;
use App\Payments\GatewayVerifyRequest;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Sleep;
use Tests\TestCase;

/**
 * ⭐ مرحله‌ی ۲ چند درگاه — ملت در payments:reconcile (پاسخ verify/settle نرسید → برگشت در پنجره‌ی ۱۶ تا ۹۰ دقیقه)
 * و در «ساعت نوبت از دست رفت» (پرداختِ settle‌شده به کارت برنمی‌گرده → کیف پول، بدون تماس با بانک).
 */
class MellatReconcileTest extends TestCase
{
    use RefreshDatabase;

    private const CREDENTIALS = ['terminal_id' => '5012345', 'username' => 'u', 'password' => 'p'];

    private Salon $salon;

    private SalonPaymentGateway $gateway;

    /** @var array<int, string> */
    private array $methods = [];

    protected function setUp(): void
    {
        parent::setUp();
        Sleep::fake();
        Notification::fake();
        $this->salon = app(CurrentSalon::class)->get();
        $this->salon->paymentGateways()->delete();
        $this->gateway = $this->salon->paymentGateways()->create(['driver' => 'mellat', 'credentials' => self::CREDENTIALS, 'priority' => 1]);
    }

    /** بانک جعلی: متد → کد پاسخ، یا 'down'. */
    private function bank(array $returns): void
    {
        $this->methods = [];
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake([MellatDriver::SERVICE_URL => function (Request $request) use ($returns) {
            preg_match('/<int:(\w+)>/', $request->body(), $m);
            $this->methods[] = $m[1];
            $answer = $returns[$m[1]] ?? 'down';

            return $answer === 'down' ? Http::failedConnection() : Http::response(
                '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body><ns2:'.$m[1].'Response xmlns:ns2="http://interfaces.core.sw.bps.com/"><return>'.$answer.'</return></ns2:'.$m[1].'Response></soap:Body></soap:Envelope>'
            );
        }]);
    }

    private function tx(array $attributes = []): PaymentTransaction
    {
        return PaymentTransaction::create($attributes + [
            'salon_id' => $this->salon->id, 'gateway_id' => $this->gateway->id, 'driver' => 'mellat', 'purpose' => 'booking',
            'amount_rial' => 250000, 'token' => 'REF', 'callback_url' => 'https://salon.test/cb',
        ]);
    }

    /** مشتری برگشت، رسید قفل شد، ولی bpVerifyRequest سه بار جواب نداد. */
    private function unansweredTx(array $attributes = []): PaymentTransaction
    {
        $tx = $this->tx($attributes);
        $this->bank(['bpVerifyRequest' => 'down']);
        $result = (new MellatDriver(self::CREDENTIALS))->verify(new GatewayVerifyRequest(250000, [
            'RefId' => 'REF', 'ResCode' => '0', 'SaleOrderId' => (string) $tx->id, 'SaleReferenceId' => '9001',
        ], 'REF', $tx->id));
        $this->assertTrue($result->raw['unanswered']);
        $tx->update(['status' => 'failed', 'verify_response' => $result->raw]);

        return $tx->fresh();
    }

    public function test_an_unanswered_mellat_verify_is_reversed_after_the_banks_verify_window(): void
    {
        $user = User::factory()->create(['phone' => '09121234567']);
        $tx = $this->unansweredTx(['user_id' => $user->id]);
        $this->bank(['bpReversalRequest' => '0']);

        $this->travel(10)->minutes();
        $this->artisan('payments:reconcile');
        $this->assertSame('failed', $tx->fresh()->status, 'تا ۱۵ دقیقه مشتری هنوز می‌تونه verify رو تکرار کنه');
        $this->assertSame([], $this->methods);

        $this->travel(10)->minutes(); // ۲۰ دقیقه بعد از بازگشت
        $this->artisan('payments:reconcile')->assertSuccessful();

        $this->assertSame('reversed', $tx->fresh()->status);
        $this->assertSame(['bpReversalRequest'], $this->methods);
        $this->assertFalse(GatewayReceipt::claim('mellat', '9001', $tx->id), 'برگشت‌خورده دیگه تایید نمی‌شه');
        Notification::assertSentTo($user, PaymentRefundedNotification::class, fn ($n) => $n->reason === 'verify_unanswered' && $n->cardToman === 25000);
    }

    public function test_the_mellat_window_closes_before_the_two_hour_reversal_limit(): void
    {
        $tx = $this->unansweredTx();
        $this->bank(['bpReversalRequest' => '0']);

        $this->travel(95)->minutes();
        $this->artisan('payments:reconcile');

        $this->assertSame('failed', $tx->fresh()->status);
        $this->assertSame([], $this->methods);
    }

    public function test_a_failed_reversal_stays_failed_and_is_retried_by_the_next_run(): void
    {
        $tx = $this->unansweredTx();
        $this->travel(20)->minutes();

        $this->bank(['bpReversalRequest' => 'down']);
        $this->artisan('payments:reconcile');
        $this->assertSame('failed', $tx->fresh()->status);

        $this->travel(5)->minutes();
        $this->bank(['bpReversalRequest' => '48']);
        $this->artisan('payments:reconcile');
        $this->assertSame('reversed', $tx->fresh()->status);
        $this->assertSame(2, $tx->fresh()->verify_response['reverse_attempts']);
    }

    public function test_saman_keeps_its_own_window(): void
    {
        $saman = $this->salon->paymentGateways()->create(['driver' => 'saman', 'credentials' => ['terminal_id' => '13012345'], 'priority' => 2]);
        $tx = PaymentTransaction::create([
            'salon_id' => $this->salon->id, 'gateway_id' => $saman->id, 'driver' => 'saman', 'purpose' => 'booking', 'amount_rial' => 1000,
            'token' => 'T', 'callback_url' => 'x', 'status' => 'failed', 'gateway_receipt' => 'RCPT', 'verify_response' => ['unanswered' => true],
        ]);
        Http::fake();

        $this->travel(20)->minutes(); // داخل پنجره‌ی ملت، ولی سامان هنوز تا ۳۰ دقیقه verify می‌پذیره
        $this->artisan('payments:reconcile');

        $this->assertSame('failed', $tx->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_a_settled_mellat_payment_for_a_lost_slot_goes_to_the_wallet_without_calling_the_bank(): void
    {
        $specialist = Specialist::factory()->create(['auto_confirm_bookings' => true]);
        $service = BeautyService::factory()->create(['price' => 200000]);
        $time = now()->addDays(2)->setTime(10, 0);
        $user = User::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id, 'service_id' => $service->id, 'specialist_id' => $specialist->id, 'booking_time' => $time,
            'payment_status' => 'unpaid', 'status' => 'pending_payment', 'prepayment_amount' => 60000,
        ]);
        $this->bank(['bpPayRequest' => '0,REF-LOST', 'bpVerifyRequest' => '0', 'bpSettleRequest' => '0']);

        $this->actingAs($user)->post(route('payment.process', $booking));
        $tx = PaymentTransaction::where('payable_id', $booking->id)->sole();

        // مدیر نوبت رو لغو کرد و مشتری دیگه‌ای همون ساعت رو گرفت
        $booking->update(['status' => 'cancelled', 'cancelled_by' => 'admin', 'cancelled_at' => now()]);
        Booking::factory()->create([
            'user_id' => User::factory()->create()->id, 'service_id' => $service->id, 'specialist_id' => $specialist->id,
            'booking_time' => $time, 'payment_status' => 'paid', 'status' => 'confirmed', 'prepayment_amount' => 60000,
        ]);

        $this->app['auth']->forgetGuards();
        $location = $this->post("/payments/return/{$tx->public_id}", [
            'RefId' => 'REF-LOST', 'ResCode' => '0', 'SaleOrderId' => (string) $tx->id, 'SaleReferenceId' => '3131',
        ])->headers->get('Location');
        $this->actingAs($user)->get($location)
            ->assertRedirect(route('bookings.failed'))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'کیف پول'));

        $this->assertSame('refunded', $tx->fresh()->status);
        $this->assertSame(60000.0, (float) $user->getOrCreateWallet()->fresh()->balance);
        $this->assertSame(['bpPayRequest', 'bpVerifyRequest', 'bpSettleRequest'], $this->methods, 'settle‌شده رو بانک برنمی‌گردونه — تلاشی هم نمی‌شه');
        Notification::assertSentTo($user, PaymentRefundedNotification::class, fn ($n) => $n->reason === 'slot_taken' && $n->walletToman === 60000);
    }
}
