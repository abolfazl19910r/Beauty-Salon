<?php

namespace Tests\Feature\Payment;

use App\Jobs\CancelUnpaidBookings;
use App\Jobs\RecheckHeldBookingPaymentJob;
use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\PaymentTransaction;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Services\Payment\HeldBookingPayment;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Sleep;
use Tests\TestCase;

/**
 * ⭐ تصمیم ۴ (۲۰۲۶-۱۰-۲۲): نوبتی که تأیید پرداختش با قطعی لحظه‌ای اینترنت نرسید، لغو نمی‌شود و payments:reconcile دوباره می‌پرسد.
 * (نوبت ۱۰۹ آزمون دستی: درخواست verify اصلاً از سرور خارج نشد و نوبتِ پرداخت‌شده همان لحظه لغو شد.)
 */
class HeldBookingPaymentTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Concerns\EnablesAllGateways;

    private const TIMEOUT = 'cURL error 28: Operation timed out after 30001 milliseconds with 0 bytes received';

    private const NOT_SENT = 'cURL error 6: Could not resolve host: gateway.zibal.ir';

    private Salon $salon;

    protected function setUp(): void
    {
        parent::setUp();
        Sleep::fake();
        Notification::fake();
        $this->salon = app(CurrentSalon::class)->get();
        $this->salon->paymentGateways()->delete();
        $this->salon->paymentGateways()->create(['driver' => 'zibal', 'credentials' => ['merchant' => 'zibal'], 'priority' => 1, 'fee_fixed_toman' => 500]);
    }

    private function zibal(array $verifyAnswers): void
    {
        $sequence = Http::sequence();
        foreach ($verifyAnswers as $answer) {
            match ($answer) {
                'timeout' => $sequence->pushFailedConnection(self::TIMEOUT),
                'not_sent' => $sequence->pushFailedConnection(self::NOT_SENT),
                default => $sequence->push($answer),
            };
        }
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake([
            'gateway.zibal.ir/v1/request' => Http::response(['trackId' => 555001, 'result' => 100]),
            'gateway.zibal.ir/v1/verify' => $sequence,
        ]);
    }

    private function verifyCalls(): int
    {
        return collect(Http::recorded())->filter(fn ($pair) => str_contains($pair[0]->url(), '/v1/verify'))->count();
    }

    private function booking(User $user): Booking
    {
        return Booking::factory()->create([
            'user_id' => $user->id, 'service_id' => BeautyService::factory()->create(['price' => 200000])->id,
            'specialist_id' => Specialist::factory()->create(['auto_confirm_bookings' => true])->id,
            'payment_status' => 'unpaid', 'status' => 'pending_payment', 'prepayment_amount' => 60000,
            'booking_time' => now()->addDays(2)->setTime(11, 0),
        ]);
    }

    /** @return array{0: PaymentTransaction, 1: \Illuminate\Testing\TestResponse} */
    private function payAndComeBack(User $user, Booking $booking): array
    {
        $this->actingAs($user)->post(route('payment.process', $booking))->assertRedirect('https://gateway.zibal.ir/start/555001');
        $tx = PaymentTransaction::where('payable_id', $booking->id)->sole();

        $this->app['auth']->forgetGuards();
        $location = $this->get("/payments/return/{$tx->public_id}?".http_build_query(['trackId' => 555001, 'success' => 1, 'status' => 2, 'orderId' => $tx->id]))->headers->get('Location');

        $response = $this->actingAs($user)->get($location);

        return [$tx->fresh(), $response];
    }

    public function test_a_verify_that_never_left_the_server_holds_the_booking_and_a_later_check_confirms_it(): void
    {
        $user = User::factory()->create(['phone' => '09121234567']);
        $booking = $this->booking($user);
        $this->zibal(['not_sent', ['result' => 100, 'status' => 1, 'amount' => 605000, 'refNumber' => 778899, 'cardNumber' => '603799******1234']]);

        [$tx, $response] = $this->payAndComeBack($user, $booking);

        $this->assertSame('verifying', $tx->status);
        $this->assertTrue($tx->verify_response['verify_not_sent']);
        $this->assertSame('pending_payment', $booking->fresh()->status, 'نوبت لغو نشد');
        $response->assertRedirect(route('bookings.show', ['booking' => $booking]))->assertSessionHas('info', HeldBookingPayment::MESSAGE);

        // نگه‌داشته: پرداخت دوباره بسته است و لغو خودکار پرداخت‌نشده‌ها کاری ندارد
        $this->assertFalse($booking->fresh()->isPayable());
        $this->assertSame(HeldBookingPayment::MESSAGE, $booking->fresh()->notPayableReason());
        $this->travel(40)->minutes();
        (new CancelUnpaidBookings)->handle(app(\App\Services\SMSService::class), app(\App\Repositories\Contracts\BookingRepositoryInterface::class));
        $this->assertSame('pending_payment', $booking->fresh()->status);

        $this->artisan('payments:reconcile')->expectsOutputToContain('paid: 1')->assertSuccessful();

        $booking->refresh();
        $this->assertSame('paid', $booking->payment_status);
        $this->assertSame('confirmed', $booking->status);
        $this->assertSame('778899', $booking->payment_reference ?? $tx->fresh()->ref_id);
        $this->assertSame('paid', $tx->fresh()->status);
        $this->assertSame(0.0, (float) $user->getOrCreateWallet()->fresh()->balance, 'نوبت ثبت شد، نه کیف پول');
        $this->assertSame(2, $this->verifyCalls());

        // اجرای دوباره چیزی را دوباره تأیید نمی‌کند
        $this->artisan('payments:reconcile');
        $this->assertSame(2, $this->verifyCalls());
    }

    public function test_a_timeout_holds_the_booking_and_a_definite_refusal_cancels_it(): void
    {
        $user = User::factory()->create();
        $booking = $this->booking($user);
        $this->zibal(['timeout', ['result' => 202, 'message' => 'تراکنش پرداخت نشده']]);

        [$tx] = $this->payAndComeBack($user, $booking);
        $this->assertSame('verifying', $tx->status);

        $this->travel(5)->minutes();
        $this->artisan('payments:reconcile')->expectsOutputToContain('not_paid: 1');

        $booking->refresh();
        $this->assertSame('cancelled', $booking->status);
        $this->assertSame('system', $booking->cancelled_by);
        $this->assertSame('پرداخت از درگاه تأیید نشد', $booking->cancellation_reason);
        $tx->refresh();
        $this->assertSame('failed', $tx->status);
        $this->assertFalse($tx->verify_response['unanswered'], 'رد قطعی به مسیر بازیابی نمی‌رود');
    }

    public function test_still_unanswered_waits_until_the_hold_ends_then_cancels_and_falls_back_to_recovery(): void
    {
        $user = User::factory()->create();
        $booking = $this->booking($user);
        $this->zibal(['timeout', 'timeout', 'timeout']);

        [$tx] = $this->payAndComeBack($user, $booking);

        $this->travel(5)->minutes();
        $this->artisan('payments:reconcile')->expectsOutputToContain('held: 1');
        $this->assertSame('verifying', $tx->fresh()->status);
        $this->assertSame(1, $tx->fresh()->verify_response['hold_checks']);
        $this->assertSame('pending_payment', $booking->fresh()->status);

        $this->travel(HeldBookingPayment::HOLD_MINUTES)->minutes();
        $this->artisan('payments:reconcile')->expectsOutputToContain('expired: 1');

        $this->assertSame('cancelled', $booking->fresh()->status);
        $tx->refresh();
        $this->assertSame('failed', $tx->status);
        $this->assertTrue($tx->verify_response['unanswered'], 'از اینجا UnansweredVerifyRecovery تا ۲۴ ساعت');
        $this->assertSame('expired', $tx->verify_response['hold_result']);
    }

    public function test_a_refresh_during_the_hold_does_not_verify_again(): void
    {
        $user = User::factory()->create();
        $booking = $this->booking($user);
        $this->zibal(['timeout']);
        [$tx] = $this->payAndComeBack($user, $booking);

        $this->app['auth']->forgetGuards();
        $location = $this->get("/payments/return/{$tx->public_id}?".http_build_query(['trackId' => 555001, 'success' => 1, 'status' => 2]))->headers->get('Location');
        $this->actingAs($user)->get($location)->assertSessionHas('info', HeldBookingPayment::MESSAGE);

        $this->assertSame(1, $this->verifyCalls());
        $this->assertSame('verifying', $tx->fresh()->status);
    }

    public function test_the_first_recheck_is_queued_a_minute_later_when_a_worker_runs_the_queue(): void
    {
        config(['queue.default' => 'database']);
        Queue::fake([RecheckHeldBookingPaymentJob::class]);
        $user = User::factory()->create();
        $this->zibal(['timeout']);

        [$tx] = $this->payAndComeBack($user, $this->booking($user));

        Queue::assertPushed(RecheckHeldBookingPaymentJob::class, fn ($job) => $job->transactionId === $tx->id
            && $job->delay !== null && now()->diffInSeconds($job->delay) >= 59);
    }

    public function test_the_app_return_page_says_pending(): void
    {
        $user = User::factory()->create();
        $booking = $this->booking($user);
        $this->zibal(['timeout']);
        $tx = PaymentTransaction::create([
            'salon_id' => $this->salon->id, 'gateway_id' => $this->salon->paymentGateways()->first()->id, 'driver' => 'zibal', 'purpose' => 'booking',
            'payable_type' => Booking::class, 'payable_id' => $booking->id, 'user_id' => $user->id,
            'amount_rial' => 605000, 'fee_rial' => 5000, 'token' => '555001', 'callback_url' => 'x', 'status' => 'pending',
        ]);

        $this->get(route('app.payment.callback', ['tx' => $tx->public_id, 'trackId' => 555001, 'success' => 1, 'status' => 2]))->assertOk()
            ->assertSee('پرداخت در حال بررسی است')
            ->assertSee('state=pending', false);
        $this->assertSame('verifying', $tx->fresh()->status);
    }

    public function test_wallet_top_ups_are_not_held(): void
    {
        $user = User::factory()->create();
        $tx = PaymentTransaction::create([
            'salon_id' => $this->salon->id, 'gateway_id' => $this->salon->paymentGateways()->first()->id, 'driver' => 'zibal', 'purpose' => 'wallet_charge',
            'user_id' => $user->id, 'amount_rial' => 1005000, 'fee_rial' => 5000, 'token' => '555002', 'callback_url' => 'x', 'status' => 'pending',
        ]);

        $this->assertFalse(HeldBookingPayment::qualifies($tx, ['verify_not_sent' => true]));
        $this->assertFalse(HeldBookingPayment::qualifies(null, ['unanswered' => true]));
        $tx->forceFill(['purpose' => 'booking', 'driver' => 'saman'])->save();
        $this->assertFalse(HeldBookingPayment::qualifies($tx, ['unanswered' => true]), 'درگاه مستقیم بانکی مسیر خودش را دارد');
    }
}
