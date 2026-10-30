<?php

namespace Tests\Feature\Payment;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Invoice;
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
 * ⭐ بسته‌ی 5ز، کار ۷ (۲۰۲۶-۱۰-۳۰): `payments:reconcile` تراکنش pending زرین‌پال را بعد از یک ساعت بی‌پرسش expired می‌کرد. اگر
 * «اعتبارسنجی خودکار» پنل زرین‌پال روشن باشد و مشتری برنگشته باشد، پول کسر شده و نوبت لغو می‌شد. حالا پیش از expired یک verify
 * (با amount_rial دیتابیس): موفق/101 ← مسیر UnansweredVerifyRecovery (کیف پول)؛ ‎-51/‎-54/‎-55 ← expired؛ بی‌پاسخ ← اجرای بعد.
 */
class ZarinpalPendingVerifyTest extends TestCase
{
    use RefreshDatabase;

    private Salon $salon;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        Sleep::fake();
        Notification::fake();
        $this->freezeSecond();
        $this->salon = app(CurrentSalon::class)->get();
        $this->salon->paymentGateways()->delete();
        $this->salon->paymentGateways()->create(['driver' => 'zarinpal', 'credentials' => ['merchant_id' => '11111111-2222-3333-4444-555555555555'], 'priority' => 1, 'is_active' => true]);
        $this->customer = User::factory()->create(['phone' => '09121234567']);
    }

    private function pending(string $purpose = 'booking', int $minutesAgo = 61, array $extra = []): PaymentTransaction
    {
        $payableId = null;
        if ($purpose === 'booking') {
            $payableId = Booking::factory()->create([
                'user_id' => $this->customer->id, 'service_id' => BeautyService::factory()->create(['price' => 200000])->id,
                'specialist_id' => Specialist::factory()->create()->id,
                'payment_status' => 'unpaid', 'status' => 'cancelled', 'prepayment_amount' => 60000,
            ])->id;
        }

        $tx = PaymentTransaction::create($extra + [
            'salon_id' => $this->salon->id, 'gateway_id' => $this->salon->paymentGateways()->sole()->id, 'driver' => 'zarinpal',
            'purpose' => $purpose, 'payable_type' => $purpose === 'booking' ? (new Booking)->getMorphClass() : null, 'payable_id' => $payableId,
            'user_id' => $this->customer->id, 'amount_rial' => 605000, 'fee_rial' => 5000,
            'token' => 'S000000000000000000000000000000'.random_int(10000, 99999), 'callback_url' => 'x', 'status' => 'pending',
        ]);
        PaymentTransaction::whereKey($tx->id)->toBase()->update(['created_at' => now()->subMinutes($minutesAgo), 'updated_at' => now()->subMinutes($minutesAgo)]);

        return $tx->fresh();
    }

    private function verifyAnswers(array $body, int $status = 200): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['*/verify.json' => Http::response($body, $status)]);
    }

    private function verifyCalls(): int
    {
        return collect(Http::recorded())->filter(fn ($pair) => str_contains($pair[0]->url(), 'verify.json'))->count();
    }

    public function test_an_abandoned_but_verified_booking_payment_goes_to_the_customers_wallet(): void
    {
        $this->verifyAnswers(['data' => ['code' => 101, 'ref_id' => 777001, 'card_pan' => '502229******5995'], 'errors' => []]);
        $tx = $this->pending();

        $this->artisan('payments:reconcile')->assertSuccessful();

        $tx->refresh();
        $this->assertSame('refunded', $tx->status);
        $this->assertSame('777001', $tx->ref_id);
        $this->assertSame(60500.0, (float) $this->customer->getOrCreateWallet()->fresh()->balance, 'کل مبلغ پرداختی، با کارمزد');
        Notification::assertSentTo($this->customer, PaymentRefundedNotification::class);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'verify.json') && $r['amount'] === 605000 && $r['authority'] === $tx->token);
    }

    public function test_an_abandoned_verified_wallet_charge_is_charged(): void
    {
        $this->verifyAnswers(['data' => ['code' => 100, 'ref_id' => 777002], 'errors' => []]);
        $tx = $this->pending('wallet_charge');

        $this->artisan('payments:reconcile')->assertSuccessful();

        $this->assertSame('paid', $tx->fresh()->status);
        $this->assertSame(60000.0, (float) $this->customer->getOrCreateWallet()->fresh()->balance, 'شارژ بدون کارمزد درگاه');
    }

    public function test_a_definitely_unpaid_payment_is_expired(): void
    {
        foreach ([-51, -54, -55] as $code) {
            $this->verifyAnswers(['data' => [], 'errors' => ['code' => $code, 'message' => 'x', 'validations' => []]], 422);
            $tx = $this->pending();

            $this->artisan('payments:reconcile')->assertSuccessful();

            $this->assertSame('expired', $tx->fresh()->status, "کد {$code}");
            $this->assertFalse($tx->fresh()->needs_attention);
        }
        $this->assertSame(0.0, (float) $this->customer->getOrCreateWallet()->fresh()->balance);
    }

    public function test_no_answer_keeps_it_pending_for_the_next_runs_then_a_human_after_a_day(): void
    {
        Http::fake(['*/verify.json' => Http::failedConnection('cURL error 28: Operation timed out')]);
        $tx = $this->pending();

        $this->artisan('payments:reconcile')->assertSuccessful();
        $this->assertSame('pending', $tx->fresh()->status);
        $this->assertSame(1, $this->verifyCalls());

        $this->travel(5)->minutes();
        $this->artisan('payments:reconcile');
        $this->assertSame(1, $this->verifyCalls(), 'هر ۱۰ دقیقه یک بار، نه هر اجرا');

        $this->travel(6)->minutes();
        $this->artisan('payments:reconcile');
        $this->assertSame(2, $this->verifyCalls());

        $this->travel(24)->hours();
        $this->artisan('payments:reconcile');
        $tx->refresh();
        $this->assertSame('expired', $tx->status);
        $this->assertTrue($tx->needs_attention, 'یک روز بی‌پاسخ ← بررسی دستی');
    }

    public function test_unexpected_error_needs_a_human(): void
    {
        $this->verifyAnswers(['data' => [], 'errors' => ['code' => -52, 'message' => 'Oops', 'validations' => []]], 422);
        $tx = $this->pending();

        $this->artisan('payments:reconcile');

        $this->assertSame('failed', $tx->fresh()->status);
        $this->assertTrue($tx->fresh()->needs_attention);
    }

    public function test_younger_than_an_hour_or_other_drivers_are_left_as_before(): void
    {
        Http::fake();
        $young = $this->pending(minutesAgo: 30);
        config(['payments.enabled_drivers' => \App\Payments\GatewayCatalog::keys()]);
        $zibal = $this->pending(extra: ['driver' => 'zibal', 'gateway_id' => null]);

        $this->artisan('payments:reconcile');

        $this->assertSame('pending', $young->fresh()->status);
        $this->assertSame('expired', $zibal->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_a_verified_platform_payment_is_flagged_for_the_super_admin(): void
    {
        $this->verifyAnswers(['data' => ['code' => 101, 'ref_id' => 777003], 'errors' => []]);
        $invoice = Invoice::factory()->create(['salon_id' => $this->salon->id, 'amount' => 60500]);
        $tx = $this->pending('subscription', extra: ['gateway_id' => null, 'payable_type' => $invoice->getMorphClass(), 'payable_id' => $invoice->id]);

        $this->artisan('payments:reconcile');

        $tx->refresh();
        $this->assertTrue($tx->needs_attention);
        $this->assertSame('failed', $tx->status);
        $this->assertSame('777003', $tx->ref_id);
    }
}
