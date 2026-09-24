<?php

namespace Tests\Feature\Payment;

use App\Models\PaymentTransaction;
use App\Models\Salon;
use App\Models\SalonPaymentGateway;
use App\Models\User;
use App\Notifications\Payment\PaymentRefundedNotification;
use App\Payments\Drivers\SamanDriver;
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
 * ⭐ مرحله‌ی ۲ چند درگاه — payments:reconcile: تراکنش‌های رهاشده + برگشت وجه سامان وقتی پاسخ verify نرسید.
 */
class SamanReconcileTest extends TestCase
{
    use RefreshDatabase;

    private const TERMINAL = '13012345';

    private Salon $salon;

    private SalonPaymentGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        Sleep::fake();
        Notification::fake();
        $this->salon = app(CurrentSalon::class)->get();
        $this->salon->paymentGateways()->delete();
        $this->gateway = $this->salon->paymentGateways()->create(['driver' => 'saman', 'credentials' => ['terminal_id' => self::TERMINAL], 'priority' => 1]);
    }

    private function tx(array $attributes = []): PaymentTransaction
    {
        return PaymentTransaction::create($attributes + [
            'salon_id' => $this->salon->id, 'gateway_id' => $this->gateway->id, 'driver' => 'saman', 'purpose' => 'booking',
            'amount_rial' => 250000, 'token' => 'TOK', 'callback_url' => 'https://salon.test/cb',
        ]);
    }

    /** مشتری برگشت، رسید قفل شد، ولی VerifyTransaction سه بار جواب نداد. */
    private function unansweredTx(): PaymentTransaction
    {
        $tx = $this->tx();
        Http::fake(['sep.shaparak.ir/*' => Http::failedConnection()]);
        $result = (new SamanDriver(['terminal_id' => self::TERMINAL]))->verify(new GatewayVerifyRequest(250000, [
            'Status' => '2', 'State' => 'OK', 'RefNum' => 'RCPT-LOST', 'ResNum' => (string) $tx->id, 'Token' => 'TOK', 'TerminalId' => self::TERMINAL,
        ], 'TOK', $tx->id));
        $this->assertFalse($result->success);
        $this->assertTrue($result->raw['unanswered']);
        $tx->update(['status' => 'failed', 'verify_response' => $result->raw]);

        return $tx->fresh();
    }

    /** stubهای Http::fake جمع می‌شن و اولین stub برنده است؛ برای مرحله‌ی بعد یک fake تازه. */
    private function refake(array $stubs = []): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake($stubs);
    }

    private static function reverseOk(): array
    {
        return ['TransactionDetail' => ['RefNum' => 'RCPT-LOST', 'TerminalNumber' => (int) self::TERMINAL, 'OrginalAmount' => 250000], 'ResultCode' => 0, 'Success' => true];
    }

    // ── payments:reconcile ──

    public function test_abandoned_pending_transactions_expire_after_an_hour_and_nothing_else_changes(): void
    {
        $old = $this->tx();
        $paid = $this->tx(['status' => 'paid']);
        $this->travel(61)->minutes();
        $fresh = $this->tx();

        $this->artisan('payments:reconcile')->assertSuccessful();

        $this->assertSame('expired', $old->fresh()->status);
        $this->assertSame('pending', $fresh->fresh()->status);
        $this->assertSame('paid', $paid->fresh()->status);
    }

    public function test_an_unanswered_saman_verify_is_reversed_once_the_customers_window_has_closed(): void
    {
        $tx = $this->unansweredTx();
        $this->refake(['sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/ReverseTransaction' => Http::response(self::reverseOk())]);

        $this->travel(10)->minutes();
        $this->artisan('payments:reconcile');
        $this->assertSame('failed', $tx->fresh()->status, 'تا ۳۰ دقیقه مشتری هنوز می‌تونه تایید رو تکرار کنه');
        Http::assertNothingSent();

        $this->travel(25)->minutes(); // ۳۵ دقیقه بعد از بازگشت
        $this->artisan('payments:reconcile')->assertSuccessful();

        $tx->refresh();
        $this->assertSame('reversed', $tx->status);
        $this->assertSame(1, $tx->verify_response['reverse_attempts']);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/ReverseTransaction')
            && $r['RefNum'] === 'RCPT-LOST' && $r['TerminalNumber'] === (int) self::TERMINAL);

        // بعد از برگشت، همون رسید دیگه برای این تراکنش هم پذیرفته نمی‌شه
        $this->assertFalse(GatewayReceipt::claim('saman', 'RCPT-LOST', $tx->id));
    }

    public function test_too_late_or_answered_failures_are_not_reversed(): void
    {
        $late = $this->unansweredTx();
        $late->forceFill(['updated_at' => now()->subMinutes(50)])->saveQuietly(); // مهلت Reverse گذشته
        $answered = $this->tx(['status' => 'failed', 'gateway_receipt' => 'R-2', 'verify_response' => ['ResultCode' => -2, 'Success' => false]]);
        $this->refake();

        $this->travel(35)->minutes();
        $this->artisan('payments:reconcile');

        $this->assertSame('failed', $late->fresh()->status);
        $this->assertSame('failed', $answered->fresh()->status, 'جواب منفی گرفته — سپ خودش برگشت می‌زنه');
        Http::assertNothingSent();
    }

    public function test_after_a_reversal_a_late_refresh_of_the_callback_neither_pays_the_booking_nor_rewrites_the_ledger(): void
    {
        $user = User::factory()->create();
        $booking = \App\Models\Booking::factory()->create([
            'user_id' => $user->id, 'payment_status' => 'unpaid', 'status' => 'pending_payment', 'prepayment_amount' => 25000,
            'service_id' => \App\Models\BeautyService::factory()->create(['price' => 100000])->id,
            'specialist_id' => \App\Models\Specialist::factory()->create()->id,
        ]);
        Http::fake(['sep.shaparak.ir/OnlinePG/OnlinePG' => Http::response(['status' => 1, 'token' => 'TOK-B'])]);
        $this->actingAs($user)->post(route('payment.process', $booking))->assertOk();
        $tx = PaymentTransaction::where('payable_id', $booking->id)->sole();
        $return = ['Status' => '2', 'State' => 'OK', 'RefNum' => 'RCPT-LOST', 'ResNum' => (string) $tx->id, 'Token' => 'TOK-B', 'TerminalId' => self::TERMINAL];

        // ۱) بازگشت مشتری؛ پاسخ verify نمی‌رسه
        $this->refake(['sep.shaparak.ir/*' => Http::failedConnection()]);
        $location = $this->post("/payments/return/{$tx->public_id}", $return)->headers->get('Location');
        $this->actingAs($user)->get($location);
        $this->assertSame('failed', $tx->fresh()->status);
        $this->assertSame('RCPT-LOST', $tx->fresh()->gateway_receipt);

        // ۲) ۳۵ دقیقه بعد، reconcile کل مبلغ رو برمی‌گردونه
        $this->refake(['sep.shaparak.ir/*' => Http::response(self::reverseOk())]);
        $this->travel(35)->minutes();
        $this->artisan('payments:reconcile');
        $this->assertSame('reversed', $tx->fresh()->status);
        Notification::assertSentToTimes($user, PaymentRefundedNotification::class, 1);
        Notification::assertSentTo($user, PaymentRefundedNotification::class, fn ($n) => $n->reason === 'verify_unanswered'
            && $n->cardToman === 25000 && $n->bookingId === $booking->id);

        // ۳) مشتری صفحه‌ی قبلی رو refresh می‌کنه: هیچ verifyی زده نمی‌شه و وضعیت برگشت‌خورده می‌مونه
        $this->refake(['sep.shaparak.ir/*' => Http::response(['ResultCode' => 2, 'Success' => true, 'TransactionDetail' => ['RefNum' => 'RCPT-LOST', 'OrginalAmount' => 250000]])]);
        $this->actingAs($user)->get($location);

        Http::assertNothingSent();
        $this->assertSame('reversed', $tx->fresh()->status);
        $this->assertSame('unpaid', $booking->fresh()->payment_status);
        Notification::assertSentToTimes($user, PaymentRefundedNotification::class, 1); // refresh پیامک دوم نمی‌فرسته
    }

    public function test_a_failed_reverse_is_retried_by_the_next_run(): void
    {
        $tx = $this->unansweredTx();
        $this->refake(['sep.shaparak.ir/*' => Http::sequence()
            ->push(['ResultCode' => -2, 'Success' => false])
            ->push(self::reverseOk())]);

        $this->travel(33)->minutes();
        $this->artisan('payments:reconcile');
        $this->assertSame('failed', $tx->fresh()->status);

        $this->travel(5)->minutes();
        $this->artisan('payments:reconcile');
        $this->assertSame('reversed', $tx->fresh()->status);
        $this->assertSame(2, $tx->fresh()->verify_response['reverse_attempts']);
    }

    public function test_dry_run_changes_nothing(): void
    {
        $pending = $this->tx();
        $lost = $this->unansweredTx();
        $this->refake();

        $this->travel(61)->minutes();
        $lost->forceFill(['updated_at' => now()->subMinutes(35)])->saveQuietly();
        $this->artisan('payments:reconcile', ['--dry-run' => true])->assertSuccessful();

        $this->assertSame('pending', $pending->fresh()->status);
        $this->assertSame('failed', $lost->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_the_command_is_scheduled_every_five_minutes(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('*/5  * * * *  php artisan payments:reconcile')->assertSuccessful();
    }
}
