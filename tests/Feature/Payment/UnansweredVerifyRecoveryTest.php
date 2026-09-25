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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Sleep;
use Tests\TestCase;

/**
 * ⭐ پاسخ تایید درگاه غیرمستقیم نرسید (۲۰۲۶-۰۹-۲۶) — مسیر کامل: callback «ناموفق» ثبت می‌کنه و نوبت لغو می‌شه؛
 * payments:reconcile بعد از ۱۰ دقیقه همون verify درگاه رو دوباره می‌پرسه: تایید شده بود → کیف پول مشتری + پیامک؛
 * رد قطعی → بسته؛ باز بی‌پاسخ → دفعه‌ی بعد (تا ۲۴ ساعت). قبلاً این پول بی‌صدا پیش سالن می‌موند.
 */
class UnansweredVerifyRecoveryTest extends TestCase
{
    use RefreshDatabase;

    private const TIMEOUT = 'cURL error 28: Operation timed out after 30001 milliseconds with 0 bytes received';

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

    /** @param  array<int, mixed>  $verifyAnswers  پاسخ‌های پی‌درپی /v1/verify */
    private function zibal(array $verifyAnswers): void
    {
        $sequence = Http::sequence();
        foreach ($verifyAnswers as $answer) {
            $answer === 'timeout' ? $sequence->pushFailedConnection(self::TIMEOUT) : $sequence->push($answer);
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

    /** نوبت → زیبال → بازگشت؛ تراکنش برمی‌گرده. */
    private function payBookingAndComeBack(User $user, Booking $booking): PaymentTransaction
    {
        $this->actingAs($user)->post(route('payment.process', $booking))->assertRedirect('https://gateway.zibal.ir/start/555001');
        $tx = PaymentTransaction::where('payable_id', $booking->id)->sole();

        $this->app['auth']->forgetGuards();
        $location = $this->get("/payments/return/{$tx->public_id}?".http_build_query(['trackId' => 555001, 'success' => 1, 'status' => 2, 'orderId' => $tx->id]))->headers->get('Location');
        $this->actingAs($user)->get($location);

        return $tx->fresh();
    }

    private function booking(User $user): Booking
    {
        return Booking::factory()->create([
            'user_id' => $user->id, 'service_id' => BeautyService::factory()->create(['price' => 200000])->id,
            'specialist_id' => Specialist::factory()->create()->id,
            'payment_status' => 'unpaid', 'status' => 'pending_payment', 'prepayment_amount' => 60000,
        ]);
    }

    public function test_a_verified_payment_whose_answer_was_lost_goes_back_to_the_customers_wallet(): void
    {
        $user = User::factory()->create(['phone' => '09121234567']);
        $booking = $this->booking($user);
        $this->zibal(['timeout', ['result' => 100, 'status' => 1, 'amount' => 605000, 'refNumber' => 99887766, 'cardNumber' => '603799******1234']]);

        $tx = $this->payBookingAndComeBack($user, $booking);
        $this->assertSame('failed', $tx->status);
        $this->assertTrue($tx->verify_response['unanswered']);
        $this->assertSame('cancelled', $booking->fresh()->status, 'callback نوبت رو لغو کرد');

        $this->travel(5)->minutes();
        $this->artisan('payments:reconcile');
        $this->assertSame(1, $this->verifyCalls(), 'تا ۱۰ دقیقه فرصت refresh خود مشتریه');

        $this->travel(10)->minutes();
        $this->artisan('payments:reconcile')->expectsOutputToContain('به کیف پول: 1')->assertSuccessful();

        $tx->refresh();
        $this->assertSame('refunded', $tx->status);
        $this->assertSame('99887766', $tx->ref_id);
        $this->assertSame(60500.0, (float) $user->getOrCreateWallet()->fresh()->balance, 'کل مبلغ پرداختی، با کارمزد');
        Notification::assertSentTo($user, PaymentRefundedNotification::class, fn ($n) => $n->reason === 'verify_unanswered'
            && $n->walletToman === 60500 && $n->cardToman === 0 && $n->bookingId === $booking->id);

        // اجرای دوباره و refresh مشتری هیچ‌کدوم دوباره پول نمی‌دن
        $this->artisan('payments:reconcile');
        $this->app['auth']->forgetGuards();
        $location = $this->get("/payments/return/{$tx->public_id}?".http_build_query(['trackId' => 555001, 'success' => 1, 'status' => 2]))->headers->get('Location');
        $this->actingAs($user)->get($location)->assertSessionHas('error', fn ($m) => str_contains($m, 'برگشت داده شده'));
        $this->assertSame(60500.0, (float) $user->getOrCreateWallet()->fresh()->balance);
        $this->assertSame(2, $this->verifyCalls());
        Notification::assertSentToTimes($user, PaymentRefundedNotification::class, 1);
    }

    public function test_a_payment_the_gateway_never_verified_is_closed_without_refunding(): void
    {
        $user = User::factory()->create();
        $this->zibal(['timeout', ['result' => 202, 'message' => 'تراکنش پرداخت نشده']]);
        $tx = $this->payBookingAndComeBack($user, $this->booking($user));

        $this->travel(15)->minutes();
        $this->artisan('payments:reconcile')->expectsOutputToContain('پرداخت‌نشده: 1');

        $tx->refresh();
        $this->assertSame('failed', $tx->status);
        $this->assertFalse($tx->verify_response['unanswered']);
        $this->assertSame('not_paid', $tx->verify_response['recovery']);
        $this->assertSame(0.0, (float) $user->getOrCreateWallet()->fresh()->balance, 'پول تاییدنشده رو خود درگاه به کارت برمی‌گردونه');
        Notification::assertNothingSent();

        $this->artisan('payments:reconcile');
        $this->assertSame(2, $this->verifyCalls(), 'بسته‌شده دوباره پرسیده نمی‌شه');
    }

    public function test_a_still_unanswered_payment_is_asked_again_until_the_24_hour_window_ends(): void
    {
        $user = User::factory()->create();
        $this->zibal(['timeout', 'timeout', 'timeout', 'timeout']);
        $tx = $this->payBookingAndComeBack($user, $this->booking($user));

        $this->travel(15)->minutes();
        $this->artisan('payments:reconcile')->expectsOutputToContain('هنوز بی‌پاسخ: 1');
        $this->travel(5)->minutes();
        $this->artisan('payments:reconcile');

        $tx->refresh();
        $this->assertSame('failed', $tx->status);
        $this->assertTrue($tx->verify_response['unanswered']);
        $this->assertSame(2, $tx->verify_response['recovery_attempts']);

        $this->travel(25)->hours();
        $this->artisan('payments:reconcile');
        $this->assertSame(3, $this->verifyCalls(), 'بعد از ۲۴ ساعت برای بررسی دستی رها می‌شه');
    }

    public function test_a_lost_wallet_top_up_answer_charges_the_wallet_when_the_gateway_had_verified(): void
    {
        $user = User::factory()->create(['phone' => '09121234567']);
        $tx = PaymentTransaction::create([
            'salon_id' => $this->salon->id, 'gateway_id' => $this->salon->paymentGateways()->first()->id, 'driver' => 'zibal', 'purpose' => 'wallet_charge',
            'user_id' => $user->id, 'amount_rial' => 1005000, 'fee_rial' => 5000, 'token' => '555002', 'callback_url' => 'x',
            'status' => 'failed', 'verify_response' => ['unanswered' => true],
        ]);
        $this->zibal([['result' => 201, 'amount' => 1005000, 'refNumber' => 4455]]);

        $this->travel(15)->minutes();
        $this->artisan('payments:reconcile');

        $this->assertSame('paid', $tx->fresh()->status);
        $wallet = $user->getOrCreateWallet()->fresh();
        $this->assertSame(100000.0, (float) $wallet->balance, 'همون مبلغ شارژ، بدون کارمزد درگاه (مثل callback موفق)');
        $this->assertDatabaseHas('user_wallet_transactions', ['wallet_id' => $wallet->id, 'type' => 'deposit', 'amount' => 100000]);
        Notification::assertSentTo($user, PaymentRefundedNotification::class, fn ($n) => $n->reason === 'charge_recovered'
            && str_contains($n->text, 'اضافه شد') && ! str_contains($n->text, 'ثبت نشد'));
    }

    public function test_a_customer_refresh_during_recovery_does_not_verify_a_second_time(): void
    {
        $user = User::factory()->create();
        $booking = $this->booking($user);
        $this->zibal(['timeout']);
        $tx = $this->payBookingAndComeBack($user, $booking);
        PaymentTransaction::whereKey($tx->id)->update(['status' => 'reconciling']); // reconcile همین لحظه داره می‌پرسه

        $this->app['auth']->forgetGuards();
        $location = $this->get("/payments/return/{$tx->public_id}?".http_build_query(['trackId' => 555001, 'success' => 1, 'status' => 2]))->headers->get('Location');
        $this->actingAs($user)->get($location)->assertSessionHas('error', fn ($m) => str_contains($m, 'در حال بررسی'));

        $this->assertSame(1, $this->verifyCalls());
        $this->assertNotSame('paid', $booking->fresh()->payment_status);
    }

    public function test_two_overlapping_recovery_runs_never_credit_twice(): void
    {
        $user = User::factory()->create();
        $tx = PaymentTransaction::create([
            'salon_id' => $this->salon->id, 'gateway_id' => $this->salon->paymentGateways()->first()->id, 'driver' => 'zibal', 'purpose' => 'booking',
            'payable_type' => Booking::class, 'payable_id' => $this->booking($user)->id,
            'user_id' => $user->id, 'amount_rial' => 605000, 'token' => '555003', 'callback_url' => 'x',
            'status' => 'failed', 'verify_response' => ['unanswered' => true],
        ]);
        $this->zibal([['result' => 100, 'amount' => 605000, 'refNumber' => 1]]);
        $staleCopy = $tx->fresh(); // اجرای دوم همین ردیف رو قبل از شروع اجرای اول خونده بود
        $recovery = app(\App\Services\Payment\UnansweredVerifyRecovery::class);

        $this->assertSame('credited', $recovery->recover($tx));
        $this->assertSame('skipped', $recovery->recover($staleCopy));

        $this->assertSame(1, $this->verifyCalls());
        $this->assertSame(60500.0, (float) $user->getOrCreateWallet()->fresh()->balance);
    }

    public function test_direct_bank_and_platform_transactions_are_left_to_their_own_paths(): void
    {
        foreach ([['driver' => 'saman', 'purpose' => 'booking'], ['driver' => 'zarinpal', 'purpose' => 'subscription']] as $row) {
            PaymentTransaction::create($row + [
                'salon_id' => $this->salon->id, 'amount_rial' => 1000, 'token' => 'T'.$row['driver'], 'callback_url' => 'x',
                'status' => 'failed', 'verify_response' => ['unanswered' => true],
            ]);
        }
        Http::fake();

        $this->travel(15)->minutes();
        $this->artisan('payments:reconcile')->expectsOutputToContain('غیرمستقیم: 0');

        Http::assertNothingSent();
    }
}
