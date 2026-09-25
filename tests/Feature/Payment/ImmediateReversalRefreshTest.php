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
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Sleep;
use Tests\TestCase;

/**
 * ⭐ پولی که درگاه بانکی همون لحظه‌ی بازگشت به کارت برگردوند (سامان: مبلغ ناهمخوان؛ ملت: settle نشد) قبلاً با
 * وضعیت «failed» ثبت می‌شد؛ refresh صفحه‌ی نتیجه دوباره verify و Reverse می‌زد و پیامک «پول برگشت» دوباره می‌رفت
 * (با probe بازتولید شد: ۲ پیامک، ۲ Reverse). حالا «reversed» ثبت می‌شه و refresh هیچ تماسی با بانک نمی‌گیره.
 */
class ImmediateReversalRefreshTest extends TestCase
{
    use RefreshDatabase;

    private Salon $salon;

    protected function setUp(): void
    {
        parent::setUp();
        Sleep::fake();
        Notification::fake();
        $this->salon = app(CurrentSalon::class)->get();
        $this->salon->paymentGateways()->delete();
    }

    private function booking(User $user): Booking
    {
        return Booking::factory()->create([
            'user_id' => $user->id, 'service_id' => BeautyService::factory()->create()->id,
            'specialist_id' => Specialist::factory()->create()->id,
            'payment_status' => 'unpaid', 'status' => 'pending_payment', 'prepayment_amount' => 60000,
        ]);
    }

    /** بازگشت POST بدون کوکی → 303 → callback با GET؛ آدرس callback برای refresh برمی‌گرده. */
    private function comeBack(User $user, PaymentTransaction $tx, array $post): string
    {
        $this->app['auth']->forgetGuards();
        $location = $this->post("/payments/return/{$tx->public_id}", $post)->headers->get('Location');
        $this->actingAs($user)->get($location);

        return $location;
    }

    public function test_a_saman_amount_mismatch_refresh_neither_reverses_nor_texts_again(): void
    {
        $this->salon->paymentGateways()->create(['driver' => 'saman', 'credentials' => ['terminal_id' => '13012345'], 'priority' => 1]);
        Http::fake([
            'sep.shaparak.ir/OnlinePG/OnlinePG' => Http::response(['status' => 1, 'token' => 'TK']),
            'sep.shaparak.ir/verifyTxnRandomSessionkey/*' => Http::response([
                'TransactionDetail' => ['RefNum' => 'R1', 'TerminalNumber' => 13012345, 'OrginalAmount' => 1000, 'RRN' => '1'],
                'ResultCode' => 0, 'Success' => true,
            ]),
        ]);
        $user = User::factory()->create(['phone' => '09121234567']);
        $booking = $this->booking($user);
        $this->actingAs($user)->post(route('payment.process', $booking));
        $tx = PaymentTransaction::sole();

        $location = $this->comeBack($user, $tx, ['Status' => '2', 'State' => 'OK', 'RefNum' => 'R1', 'ResNum' => (string) $tx->id,
            'Token' => 'TK', 'TerminalId' => '13012345', 'MID' => '13012345']);
        $this->assertSame('reversed', $tx->fresh()->status);

        $this->actingAs($user)->get($location)->assertSessionHas('error', fn ($m) => str_contains($m, 'برگشت داده شده'));

        $this->assertSame(1, collect(Http::recorded())->filter(fn ($pair) => str_contains($pair[0]->url(), 'ReverseTransaction'))->count());
        $this->assertSame(1, collect(Http::recorded())->filter(fn ($pair) => str_contains($pair[0]->url(), 'VerifyTransaction'))->count());
        Notification::assertSentToTimes($user, PaymentRefundedNotification::class, 1);
        $this->assertNotSame('paid', $booking->fresh()->payment_status);
    }

    public function test_a_mellat_settle_failure_refresh_neither_reverses_nor_texts_again(): void
    {
        $this->salon->paymentGateways()->create(['driver' => 'mellat', 'credentials' => ['terminal_id' => '1', 'username' => 'u', 'password' => 'p'], 'priority' => 1]);
        $methods = [];
        Http::fake([MellatDriver::SERVICE_URL => function (Request $request) use (&$methods) {
            preg_match('/<int:(\w+)>/', $request->body(), $m);
            $methods[] = $m[1];
            $return = ['bpPayRequest' => '0,REF', 'bpVerifyRequest' => '0', 'bpSettleRequest' => '61', 'bpReversalRequest' => '0'][$m[1]];

            return Http::response('<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body><ns2:x xmlns:ns2="http://interfaces.core.sw.bps.com/"><return>'.$return.'</return></ns2:x></soap:Body></soap:Envelope>');
        }]);
        $user = User::factory()->create(['phone' => '09121234567']);
        $booking = $this->booking($user);
        $this->actingAs($user)->post(route('payment.process', $booking));
        $tx = PaymentTransaction::sole();

        $location = $this->comeBack($user, $tx, ['RefId' => 'REF', 'ResCode' => '0', 'SaleOrderId' => (string) $tx->id, 'SaleReferenceId' => '777']);
        $this->actingAs($user)->get($location);

        $this->assertSame('reversed', $tx->fresh()->status);
        $this->assertSame(['bpPayRequest', 'bpVerifyRequest', 'bpSettleRequest', 'bpReversalRequest'], $methods);
        Notification::assertSentToTimes($user, PaymentRefundedNotification::class, 1);
    }
}
