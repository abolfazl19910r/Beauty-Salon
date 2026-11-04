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
use App\Payments\Drivers\ZarinpalDriver;
use App\Support\CurrentSalon;
use App\Support\PlatformZarinpal;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Sleep;
use Tests\TestCase;

/**
 * ⭐ بسته‌ی 5ح (۲۰۲۶-۱۱-۰۴): `payments:zarinpal-unverified` — فهرست `unVerified` زرین‌پال (پرداخت موفقِ verifyنشده) با تراکنش‌های
 * بسته‌شده‌ی ما تطبیق می‌خورد. تصمیم‌ها: ۷ روز اخیر ← verify و واریز خودکار، قدیمی‌تر ← فقط «نیازمند بررسی»؛ ساعتی؛ اتوریتی ناشناس فقط لاگ.
 */
class ZarinpalUnverifiedTest extends TestCase
{
    use RefreshDatabase;

    private const MERCHANT = '11111111-2222-3333-4444-555555555555';

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
        $this->salon->paymentGateways()->create(['driver' => 'zarinpal', 'credentials' => ['merchant_id' => self::MERCHANT], 'priority' => 1, 'is_active' => true]);
        $this->customer = User::factory()->create(['phone' => '09121234567']);
        config(['services.zarinpal.merchant_id' => 'aaaaaaaa-0000-0000-0000-bbbbbbbbbbbb', 'services.zarinpal.sandbox' => true]);
    }

    private function tx(string $status = 'expired', int $hoursAgo = 30, string $purpose = 'booking', array $extra = []): PaymentTransaction
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
            'token' => 'A0000000000000000000000000000'.random_int(1000000, 9999999), 'callback_url' => 'x', 'status' => $status,
        ]);
        PaymentTransaction::whereKey($tx->id)->toBase()->update(['created_at' => now()->subHours($hoursAgo), 'updated_at' => now()->subHours($hoursAgo)]);

        return $tx->fresh();
    }

    /** @param  array<string, list<array>>  $lists  merchant_id → authorities */
    private function fakeZarinpal(array $lists, array $verify = ['data' => ['code' => 100, 'ref_id' => 880001, 'card_pan' => '502229******5995'], 'errors' => []]): void
    {
        Http::fake([
            '*/unVerified.json' => function ($request) use ($lists) {
                return Http::response(['data' => ['code' => '100', 'message' => 'Success', 'authorities' => $lists[$request['merchant_id']] ?? []]], 200);
            },
            '*/verify.json' => Http::response($verify, isset($verify['errors']['code']) ? 422 : 200),
        ]);
    }

    private function listed(PaymentTransaction $tx, ?int $amount = null): array
    {
        return ['authority' => $tx->token, 'amount' => $amount ?? (int) $tx->amount_rial, 'callback_url' => 'x', 'referer' => '/', 'date' => '2026-11-03 10:00:00'];
    }

    public function test_the_driver_reads_both_documented_shapes_of_the_list(): void
    {
        $item = ['authority' => 'A0000000000000000000000000000wwOGYpd', 'amount' => 50500, 'date' => '2020-07-01 17:33:25'];
        $driver = new ZarinpalDriver(['merchant_id' => self::MERCHANT], true);

        Http::fake(['*/unVerified.json' => Http::response(['data' => ['code' => '100', 'authorities' => [$item]]])]);
        $this->assertSame([['authority' => $item['authority'], 'amount' => 50500, 'date' => $item['date']]], $driver->unverified()['authorities']);

        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['*/unVerified.json' => Http::response(['data' => ['code' => 100, 'authorities' => json_encode([$item])]])]);
        $this->assertSame($item['authority'], $driver->unverified()['authorities'][0]['authority'] ?? null, 'authorities به‌صورت JSON Encode (جدول مستندات)');
        Http::assertSent(fn ($r) => str_contains($r->url(), 'sandbox.zarinpal.com/pg/v4/payment/unVerified.json') && $r['merchant_id'] === self::MERCHANT);

        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['*/unVerified.json' => Http::response(['data' => [], 'errors' => ['code' => -10, 'message' => 'x']], 422)]);
        $this->assertFalse($driver->unverified()['ok']);
    }

    public function test_an_expired_booking_payment_still_held_by_zarinpal_is_verified_and_goes_to_the_wallet(): void
    {
        $tx = $this->tx('expired', 30, 'booking', ['needs_attention' => true]);
        $this->fakeZarinpal([self::MERCHANT => [$this->listed($tx)]]);

        $this->artisan('payments:zarinpal-unverified')->assertSuccessful();

        $tx->refresh();
        $this->assertSame('refunded', $tx->status);
        $this->assertSame('880001', $tx->ref_id);
        $this->assertFalse($tx->needs_attention, 'پرچم «نیازمند بررسی» قبلی (gave_up) برداشته می‌شود');
        $this->assertSame('verified_from_unverified_list', $tx->verify_response['recovery_source']);
        $this->assertSame(60500.0, (float) $this->customer->getOrCreateWallet()->fresh()->balance);
        Notification::assertSentTo($this->customer, PaymentRefundedNotification::class);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'verify.json') && $r['amount'] === 605000 && $r['authority'] === $tx->token && $r['merchant_id'] === self::MERCHANT);

        $this->artisan('payments:zarinpal-unverified')->assertSuccessful();
        $this->assertSame(60500.0, (float) $this->customer->getOrCreateWallet()->fresh()->balance, 'اجرای دوباره دو بار واریز نمی‌کند');
    }

    public function test_a_failed_wallet_charge_is_charged(): void
    {
        $tx = $this->tx('failed', 30, 'wallet_charge');
        $this->fakeZarinpal([self::MERCHANT => [$this->listed($tx)]]);

        $this->artisan('payments:zarinpal-unverified')->assertSuccessful();

        $this->assertSame('paid', $tx->fresh()->status);
        $this->assertSame(60000.0, (float) $this->customer->getOrCreateWallet()->fresh()->balance);
    }

    public function test_older_than_seven_days_or_a_different_amount_only_flags_for_a_human(): void
    {
        $old = $this->tx('expired', 24 * 8);
        $mismatch = $this->tx('expired', 30);
        $this->fakeZarinpal([self::MERCHANT => [$this->listed($old), $this->listed($mismatch, 999000)]]);

        $this->artisan('payments:zarinpal-unverified')->assertSuccessful();

        foreach ([$old, $mismatch] as $tx) {
            $tx->refresh();
            $this->assertSame('expired', $tx->status);
            $this->assertTrue($tx->needs_attention);
        }
        $this->assertSame('unverified_too_old', $old->verify_response['recovery']);
        $this->assertSame('unverified_amount_mismatch', $mismatch->verify_response['recovery']);
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'verify.json'));
        $this->assertSame(0.0, (float) $this->customer->getOrCreateWallet()->fresh()->balance);
    }

    public function test_a_verify_that_does_not_succeed_restores_the_previous_status(): void
    {
        $tx = $this->tx('expired', 30);
        $this->fakeZarinpal([self::MERCHANT => [$this->listed($tx)]], ['data' => [], 'errors' => ['code' => -51, 'message' => 'x', 'validations' => []]]);

        $this->artisan('payments:zarinpal-unverified')->assertSuccessful();

        $tx->refresh();
        $this->assertSame('expired', $tx->status);
        $this->assertFalse($tx->needs_attention);
        $this->assertSame(-51, $tx->verify_response['last_unverified_check']);
    }

    public function test_verify_answers_needing_a_human_are_flagged(): void
    {
        $tx = $this->tx('failed', 30);
        $this->fakeZarinpal([self::MERCHANT => [$this->listed($tx)]], ['data' => [], 'errors' => ['code' => -52, 'message' => 'x', 'validations' => []]]);

        $this->artisan('payments:zarinpal-unverified')->assertSuccessful();

        $this->assertSame('failed', $tx->fresh()->status);
        $this->assertTrue($tx->fresh()->needs_attention);
    }

    public function test_pending_paid_and_still_recovering_transactions_are_left_to_their_own_paths(): void
    {
        $pending = $this->tx('pending', 2);
        $paid = $this->tx('paid', 30);
        $recovering = $this->tx('failed', 3, 'booking', ['verify_response' => ['unanswered' => true]]);
        $this->fakeZarinpal([self::MERCHANT => [$this->listed($pending), $this->listed($paid), $this->listed($recovering)]]);

        $this->artisan('payments:zarinpal-unverified')->assertSuccessful();

        $this->assertSame('pending', $pending->fresh()->status);
        $this->assertSame('paid', $paid->fresh()->status);
        $this->assertSame('failed', $recovering->fresh()->status);
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'verify.json'));
    }

    public function test_unknown_authorities_and_other_salons_payments_are_only_logged(): void
    {
        $other = Salon::factory()->create();
        $otherTx = $this->tx('expired', 30, 'wallet_charge', ['salon_id' => $other->id]);
        $this->fakeZarinpal([self::MERCHANT => [
            ['authority' => 'A00000000000000000000000000000NOTOURS', 'amount' => 10000, 'date' => null],
            $this->listed($otherTx),
        ]]);

        $this->artisan('payments:zarinpal-unverified')
            ->expectsOutputToContain('ناشناس: 2')
            ->assertSuccessful();

        $this->assertSame('expired', $otherTx->fresh()->status);
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'verify.json'));
    }

    public function test_platform_payments_use_the_platform_merchant_and_a_late_subscription_payment_lands_on_the_invoice(): void
    {
        $invoice = Invoice::factory()->create(['salon_id' => $this->salon->id, 'status' => 'pending', 'amount' => 60500]);
        $tx = $this->tx('expired', 30, 'subscription', ['gateway_id' => null, 'payable_type' => (new Invoice)->getMorphClass(), 'payable_id' => $invoice->id]);
        $platform = PlatformZarinpal::merchantId();
        $this->fakeZarinpal([$platform => [$this->listed($tx)], self::MERCHANT => [$this->listed($tx)]]);

        $this->artisan('payments:zarinpal-unverified')->assertSuccessful();

        $this->assertSame('paid', $tx->fresh()->status);
        $this->assertSame(60500, (int) $invoice->fresh()->paid_amount);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'verify.json') && $r['merchant_id'] === $platform);
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'verify.json') && $r['merchant_id'] === self::MERCHANT);
    }

    public function test_inactive_gateways_are_asked_too_and_placeholders_are_skipped(): void
    {
        $this->salon->paymentGateways()->update(['is_active' => false]);
        $this->salon->paymentGateways()->create(['driver' => 'zarinpal', 'credentials' => ['merchant_id' => 'your-merchant-id'], 'priority' => 2, 'is_active' => true]);
        $this->fakeZarinpal([]);

        $this->artisan('payments:zarinpal-unverified')->assertSuccessful();

        $asked = collect(Http::recorded())->map(fn ($p) => $p[0]['merchant_id'])->all();
        $this->assertContains(self::MERCHANT, $asked);
        $this->assertContains(PlatformZarinpal::merchantId(), $asked);
        $this->assertNotContains('your-merchant-id', $asked);
    }

    public function test_dry_run_changes_nothing_and_it_runs_hourly(): void
    {
        $tx = $this->tx('expired', 30);
        $this->fakeZarinpal([self::MERCHANT => [$this->listed($tx)]]);

        $this->artisan('payments:zarinpal-unverified --dry-run')
            ->expectsOutputToContain("#{$tx->id}")
            ->assertSuccessful();

        $this->assertSame('expired', $tx->fresh()->status);
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'verify.json'));

        $event = collect(app(Schedule::class)->events())->first(fn ($e) => str_contains((string) $e->command, 'payments:zarinpal-unverified'));
        $this->assertNotNull($event);
        $this->assertSame('0 * * * *', $event->expression);
    }
}
