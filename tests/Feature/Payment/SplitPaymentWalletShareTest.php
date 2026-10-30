<?php

namespace Tests\Feature\Payment;

use App\Jobs\CancelUnpaidBookings;
use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\PaymentTransaction;
use App\Models\Specialist;
use App\Models\User;
use App\Notifications\Payment\PaymentRefundedNotification;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Services\SMSService;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Sleep;
use Tests\TestCase;

/**
 * سهم کیف پولِ پرداخت ترکیبی (کیف پول + درگاه) روی ردیف تراکنش، نه session (۲۰۲۶-۱۰-۱۱).
 * باگ: مشتری از بانک برنمی‌گشت، نوبت خودکار لغو می‌شد و سهم کیف پول هیچ‌وقت برنمی‌گشت.
 */
class SplitPaymentWalletShareTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Concerns\EnablesAllGateways;

    private Specialist $specialist;

    private BeautyService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Sleep::fake();
        Notification::fake();
        $salon = app(CurrentSalon::class)->get();
        $salon->paymentGateways()->delete();
        $salon->paymentGateways()->create(['driver' => 'zibal', 'credentials' => ['merchant' => 'zibal'], 'priority' => 1]);
        $this->specialist = Specialist::factory()->create(['auto_confirm_bookings' => false]);
        $this->service = BeautyService::factory()->create(['price' => 200000]);
        $this->user = User::factory()->create();
        $this->user->getOrCreateWallet()->update(['balance' => 20000]);
    }

    private function booking(array $extra = []): Booking
    {
        return Booking::factory()->create(array_merge([
            'user_id' => $this->user->id, 'service_id' => $this->service->id, 'specialist_id' => $this->specialist->id,
            'booking_time' => now()->addDays(2)->setTime(10, 0), 'payment_status' => 'unpaid', 'status' => 'pending_payment', 'prepayment_amount' => 60000,
        ], $extra));
    }

    private function balance(): float
    {
        return (float) $this->user->getOrCreateWallet()->fresh()->balance;
    }

    private function payWithWallet(Booking $booking, int $trackId = 8): PaymentTransaction
    {
        Http::fake(['gateway.zibal.ir/v1/request' => Http::response(['result' => 100, 'trackId' => $trackId])]);
        $this->actingAs($this->user)->post(route('payment.wallet', $booking), ['use_wallet' => 1, 'wallet_amount' => 20000]);

        return PaymentTransaction::latest('id')->firstOrFail();
    }

    private function comeBack(PaymentTransaction $tx, array $params): \Illuminate\Testing\TestResponse
    {
        $location = $this->get("/payments/return/{$tx->public_id}?".http_build_query($params))->headers->get('Location');

        return $this->actingAs($this->user)->get($location);
    }

    private function runAutoCancel(): void
    {
        (new CancelUnpaidBookings)->handle(app(SMSService::class), app(BookingRepositoryInterface::class));
    }

    public function test_the_wallet_share_is_kept_on_the_transaction_not_in_the_session(): void
    {
        $tx = $this->payWithWallet($this->booking());

        $this->assertSame(0.0, $this->balance());
        $this->assertSame(20000.0, $tx->wallet_toman);
        $this->assertSame('held', $tx->wallet_state);
        $this->assertSame(400000, $tx->amount_rial, 'فقط باقی‌مانده (۴۰٬۰۰۰ تومان) به درگاه');
    }

    public function test_an_abandoned_split_payment_returns_the_wallet_share_when_the_booking_is_auto_cancelled(): void
    {
        $booking = $this->booking();
        $this->payWithWallet($booking);

        $this->runAutoCancel();
        $this->assertSame('pending_payment', $booking->fresh()->status, 'مشتری هنوز ممکن است در بانک باشد');
        $this->assertSame(0.0, $this->balance());

        $this->travel(PaymentTransaction::PENDING_LIFETIME_MINUTES + 1)->minutes();
        $this->runAutoCancel();
        $this->runAutoCancel();

        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertSame(20000.0, $this->balance(), 'سهم کیف پول برگشت — فقط یک بار');
        $this->assertSame('released', PaymentTransaction::sole()->wallet_state);
    }

    public function test_cancelling_an_unpaid_booking_by_hand_also_returns_the_held_share(): void
    {
        $booking = $this->booking();
        $this->payWithWallet($booking);

        $booking->update(['status' => 'cancelled', 'cancelled_by' => 'admin', 'cancelled_at' => now()]);

        $this->assertSame(20000.0, $this->balance());
    }

    public function test_a_failed_return_from_the_bank_gives_the_share_back_exactly_once(): void
    {
        $booking = $this->booking();
        $tx = $this->payWithWallet($booking);
        Http::fake(['gateway.zibal.ir/v1/verify' => Http::response(['result' => 202])]);

        $this->comeBack($tx, ['trackId' => 8, 'success' => 0, 'status' => 3])->assertRedirect(route('bookings.failed'));
        $this->comeBack($tx, ['trackId' => 8, 'success' => 0, 'status' => 3]);

        $this->assertSame(20000.0, $this->balance());
        $this->assertSame('cancelled', $booking->fresh()->status);
    }

    public function test_a_successful_split_payment_settles_the_share_into_the_booking(): void
    {
        $booking = $this->booking();
        $tx = $this->payWithWallet($booking);
        Http::fake(['gateway.zibal.ir/v1/verify' => Http::response(['result' => 100, 'amount' => 400000, 'refNumber' => 5])]);

        $this->comeBack($tx, ['trackId' => 8, 'success' => 1, 'status' => 2])
            ->assertRedirect(route('bookings.success', ['id' => $booking->id]));

        $booking->refresh();
        $this->assertSame('paid', $booking->payment_status);
        $this->assertSame('wallet_gateway', $booking->payment_details['method']);
        $this->assertEquals(20000, $booking->payment_details['wallet_amount']);
        $this->assertEquals(40000, $booking->payment_details['gateway_amount']);
        $this->assertSame('settled', $tx->fresh()->wallet_state);
        $this->assertSame(0.0, $this->balance());
    }

    public function test_an_earlier_abandoned_attempt_is_returned_when_a_later_attempt_pays_the_booking(): void
    {
        $this->user->getOrCreateWallet()->update(['balance' => 30000]);
        $booking = $this->booking();
        $first = $this->payWithWallet($booking, 8);
        $this->assertSame(10000.0, $this->balance());

        Http::fake(['gateway.zibal.ir/v1/request' => Http::response(['result' => 100, 'trackId' => 9])]);
        $this->actingAs($this->user)->post(route('payment.wallet', $booking), ['use_wallet' => 1, 'wallet_amount' => 10000]);
        $second = PaymentTransaction::latest('id')->first();
        Http::fake(['gateway.zibal.ir/v1/verify' => Http::response(['result' => 100, 'amount' => 500000, 'refNumber' => 6])]);
        $this->comeBack($second, ['trackId' => 9, 'success' => 1, 'status' => 2]);

        $this->assertSame('paid', $booking->fresh()->payment_status);
        $this->assertSame('released', $first->fresh()->wallet_state);
        $this->assertSame(20000.0, $this->balance(), 'سهم ۲۰٬۰۰۰ تلاش اول برگشت؛ ۱۰٬۰۰۰ تلاش دوم خرج شد');
    }

    public function test_the_share_comes_back_at_once_when_the_gateway_cannot_be_started(): void
    {
        Http::fake(['gateway.zibal.ir/v1/request' => Http::response(['result' => 102, 'message' => 'merchant not found'])]);

        $this->actingAs($this->user)->post(route('payment.wallet', $this->booking()), ['use_wallet' => 1, 'wallet_amount' => 20000]);

        $this->assertSame(20000.0, $this->balance());
        $this->assertSame('released', PaymentTransaction::sole()->wallet_state);
    }

    public function test_a_late_success_after_the_share_was_returned_refunds_the_card_part_instead_of_reviving_the_booking(): void
    {
        $booking = $this->booking();
        $tx = $this->payWithWallet($booking);
        $this->travel(PaymentTransaction::PENDING_LIFETIME_MINUTES + 1)->minutes();
        $this->runAutoCancel();
        $this->assertSame(20000.0, $this->balance());

        Http::fake(['gateway.zibal.ir/v1/verify' => Http::response(['result' => 100, 'amount' => 400000, 'refNumber' => 7])]);
        $this->comeBack($tx, ['trackId' => 8, 'success' => 1, 'status' => 2])->assertRedirect(route('bookings.failed'));

        $booking->refresh();
        $this->assertSame('cancelled', $booking->status);
        $this->assertSame('unpaid', $booking->payment_status);
        $this->assertSame(60000.0, $this->balance(), '۲۰٬۰۰۰ سهم کیف پول + ۴۰٬۰۰۰ پرداخت درگاه، هر کدام یک بار');
        Notification::assertSentTo($this->user, PaymentRefundedNotification::class, fn ($n) => $n->reason === 'booking_cancelled' && $n->walletToman === 40000);
    }
}
