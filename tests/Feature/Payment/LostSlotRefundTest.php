<?php

namespace Tests\Feature\Payment;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\PaymentTransaction;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Notifications\Payment\PaymentRefundedNotification;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Sleep;
use Tests\TestCase;

/**
 * ⭐ درگاه پول رو گرفت ولی ساعت نوبت در این فاصله به نفر دیگه‌ای رسید (تصمیم ابوالفضل ۲۰۲۶-۰۹-۲۶):
 * سامان → Reverse به کارت (اگه نشد کیف پول)؛ بقیه‌ی درگاه‌ها → کیف پول؛ بخش کیف پولی پرداخت ترکیبی → کیف پول.
 * قبل از این، مشتری «پرداخت ناموفق» می‌دید و پول در حساب درگاه سالن می‌موند.
 */
class LostSlotRefundTest extends TestCase
{
    use RefreshDatabase;

    private Salon $salon;

    private Specialist $specialist;

    private BeautyService $service;

    private \Carbon\Carbon $time;

    protected function setUp(): void
    {
        parent::setUp();
        Sleep::fake();
        Notification::fake();
        $this->salon = app(CurrentSalon::class)->get();
        $this->salon->paymentGateways()->delete();
        $this->specialist = Specialist::factory()->create(['auto_confirm_bookings' => true]);
        $this->service = BeautyService::factory()->create(['price' => 200000]);
        $this->time = now()->addDays(2)->setTime(10, 0);
    }

    private function booking(User $user): Booking
    {
        return Booking::factory()->create([
            'user_id' => $user->id, 'service_id' => $this->service->id, 'specialist_id' => $this->specialist->id,
            'booking_time' => $this->time, 'payment_status' => 'unpaid', 'status' => 'pending_payment', 'prepayment_amount' => 60000,
        ]);
    }

    /** نوبت در این فاصله لغو شد (مثلاً مدیر سالن) و مشتری دیگه‌ای همون ساعت رو گرفت. */
    private function loseTheSlot(Booking $booking): void
    {
        $booking->update(['status' => 'cancelled', 'cancelled_by' => 'admin', 'cancelled_at' => now()]);
        $this->booking(User::factory()->create())->update(['status' => 'confirmed', 'payment_status' => 'paid']);
    }

    private function comeBack(User $user, PaymentTransaction $tx, array $gatewayParams): \Illuminate\Testing\TestResponse
    {
        $location = $this->get("/payments/return/{$tx->public_id}?".http_build_query($gatewayParams))->headers->get('Location');

        return $this->actingAs($user)->get($location);
    }

    private function zibalGateway(int $feeToman = 0): void
    {
        $this->salon->paymentGateways()->create(['driver' => 'zibal', 'credentials' => ['merchant' => 'zibal'], 'priority' => 1, 'fee_fixed_toman' => $feeToman]);
    }

    public function test_a_non_saman_payment_for_a_lost_slot_goes_back_to_the_wallet_including_the_fee_and_only_once(): void
    {
        $this->zibalGateway(500);
        Http::fake([
            'gateway.zibal.ir/v1/request' => Http::response(['result' => 100, 'trackId' => 7]),
            'gateway.zibal.ir/v1/verify' => Http::sequence()->push(['result' => 100, 'amount' => 605000, 'refNumber' => 1])->push(['result' => 201, 'amount' => 605000]),
        ]);
        $user = User::factory()->create();
        $booking = $this->booking($user);
        $this->actingAs($user)->post(route('payment.process', $booking));
        $tx = PaymentTransaction::sole();
        $this->loseTheSlot($booking);

        $this->comeBack($user, $tx, ['trackId' => 7, 'success' => 1, 'status' => 2])
            ->assertRedirect(route('bookings.failed'))
            ->assertSessionHas('error', fn ($m) => str_contains($m, '60,500 تومان به کیف پول'));

        $this->assertSame(60500.0, (float) $user->getOrCreateWallet()->fresh()->balance, 'کل مبلغ پرداختی، با کارمزد');
        $this->assertSame('refunded', $tx->fresh()->status);
        $this->assertSame('slot_taken', $tx->fresh()->verify_response['refund']['reason']);
        $booking->refresh();
        $this->assertSame('cancelled', $booking->status);
        $this->assertSame('unpaid', $booking->payment_status);
        $this->assertStringContainsString('آزاد نبود', $booking->cancellation_reason);

        // refresh صفحه‌ی callback: نه دوباره verify، نه دوباره برگشت پول
        $this->comeBack($user, $tx, ['trackId' => 7, 'success' => 1, 'status' => 2])->assertRedirect(route('bookings.failed'));
        $this->assertSame(60500.0, (float) $user->getOrCreateWallet()->fresh()->balance);
        $this->assertSame(1, count(Http::recorded(fn (Request $r) => str_contains($r->url(), '/v1/verify'))));

        // مشتری پیامک گرفت — فقط یک بار، با مبلغ کیف پول
        Notification::assertSentToTimes($user, PaymentRefundedNotification::class, 1);
        Notification::assertSentTo($user, PaymentRefundedNotification::class, fn ($n) => $n->reason === 'slot_taken'
            && $n->walletToman === 60500 && $n->cardToman === 0 && $n->bookingId === $booking->id
            && str_contains($n->text, '۶۰٬۵۰۰ تومان به کیف پول'));
    }

    public function test_the_wallet_share_of_a_split_payment_comes_back_too(): void
    {
        $this->zibalGateway();
        Http::fake([
            'gateway.zibal.ir/v1/request' => Http::response(['result' => 100, 'trackId' => 8]),
            'gateway.zibal.ir/v1/verify' => Http::response(['result' => 100, 'amount' => 400000, 'refNumber' => 2]),
        ]);
        $user = User::factory()->create();
        $user->getOrCreateWallet()->update(['balance' => 20000]);
        $booking = $this->booking($user);

        $this->actingAs($user)->post(route('payment.wallet', $booking), ['use_wallet' => 1, 'wallet_amount' => 20000]);
        $this->assertSame(0.0, (float) $user->getOrCreateWallet()->fresh()->balance);
        $tx = PaymentTransaction::sole();
        $this->loseTheSlot($booking);

        $this->comeBack($user, $tx, ['trackId' => 8, 'success' => 1, 'status' => 2])->assertRedirect(route('bookings.failed'));

        $this->assertSame(60000.0, (float) $user->getOrCreateWallet()->fresh()->balance, '۲۰٬۰۰۰ کیف پول + ۴۰٬۰۰۰ درگاه');
    }

    private function samanGateway(): void
    {
        $this->salon->paymentGateways()->create(['driver' => 'saman', 'credentials' => ['terminal_id' => '13012345'], 'priority' => 1]);
    }

    private function samanReturn(PaymentTransaction $tx): array
    {
        return ['Status' => '2', 'State' => 'OK', 'RefNum' => 'RC-9', 'ResNum' => (string) $tx->id, 'Token' => 'ST', 'TerminalId' => '13012345'];
    }

    private static function samanOk(): array
    {
        return ['ResultCode' => 0, 'Success' => true, 'TransactionDetail' => ['RefNum' => 'RC-9', 'TerminalNumber' => 13012345, 'OrginalAmount' => 600000, 'RRN' => '55']];
    }

    public function test_a_saman_payment_for_a_lost_slot_is_reversed_to_the_card_not_the_wallet(): void
    {
        $this->samanGateway();
        Http::fake([
            'sep.shaparak.ir/OnlinePG/OnlinePG' => Http::response(['status' => 1, 'token' => 'ST']),
            'sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/VerifyTransaction' => Http::response(self::samanOk()),
            'sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/ReverseTransaction' => Http::response(self::samanOk()),
        ]);
        $user = User::factory()->create();
        $booking = $this->booking($user);
        $this->actingAs($user)->post(route('payment.process', $booking));
        $tx = PaymentTransaction::sole();
        $this->loseTheSlot($booking);

        $this->comeBack($user, $tx, $this->samanReturn($tx))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'به کارت بانکی شما'));

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/ReverseTransaction') && $r['RefNum'] === 'RC-9');
        $this->assertSame('reversed', $tx->fresh()->status);
        Notification::assertSentTo($user, PaymentRefundedNotification::class, fn ($n) => $n->cardToman === 60000 && $n->walletToman === 0
            && str_contains($n->text, 'به کارت بانکی شما'));
        $this->assertSame(0.0, (float) $user->getOrCreateWallet()->fresh()->balance);
    }

    public function test_when_the_saman_reverse_fails_the_money_goes_to_the_wallet(): void
    {
        $this->samanGateway();
        Http::fake([
            'sep.shaparak.ir/OnlinePG/OnlinePG' => Http::response(['status' => 1, 'token' => 'ST']),
            'sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/VerifyTransaction' => Http::response(self::samanOk()),
            'sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/ReverseTransaction' => Http::response(['ResultCode' => -6, 'Success' => false]),
        ]);
        $user = User::factory()->create();
        $booking = $this->booking($user);
        $this->actingAs($user)->post(route('payment.process', $booking));
        $tx = PaymentTransaction::sole();
        $this->loseTheSlot($booking);

        $this->comeBack($user, $tx, $this->samanReturn($tx))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'کیف پول'));

        $this->assertSame('refunded', $tx->fresh()->status);
        $this->assertSame(60000.0, (float) $user->getOrCreateWallet()->fresh()->balance);
    }

    public function test_a_late_payment_for_a_cancelled_booking_whose_slot_is_still_free_still_confirms_it(): void
    {
        $this->zibalGateway();
        Http::fake([
            'gateway.zibal.ir/v1/request' => Http::response(['result' => 100, 'trackId' => 9]),
            'gateway.zibal.ir/v1/verify' => Http::response(['result' => 100, 'amount' => 600000, 'refNumber' => 3]),
        ]);
        $user = User::factory()->create();
        $booking = $this->booking($user);
        $this->actingAs($user)->post(route('payment.process', $booking));
        $booking->update(['status' => 'cancelled']);

        $this->comeBack($user, PaymentTransaction::sole(), ['trackId' => 9, 'success' => 1, 'status' => 2])
            ->assertRedirect(route('bookings.success', ['id' => $booking->id]));

        $this->assertSame('paid', $booking->fresh()->payment_status);
        $this->assertSame(0.0, (float) $user->getOrCreateWallet()->fresh()->balance);
        Notification::assertNotSentTo($user, PaymentRefundedNotification::class);
    }
}
