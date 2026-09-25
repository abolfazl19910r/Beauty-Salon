<?php

namespace Tests\Feature\Payment;

use App\Events\Withdrawal\Approved\WithdrawalApproved;
use App\Events\Withdrawal\Rejected\WithdrawalRejected;
use App\Jobs\ProcessWithdrawalJob;
use App\Models\Salon;
use App\Models\SalonPaymentGateway;
use App\Models\Specialist;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Payments\Drivers\VandarPayoutDriver;
use App\Payments\PayoutManager;
use App\Payments\PayoutRequest;
use App\Services\Payment\SalonPayoutService;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * ⭐ تسویه‌ی خودکار متخصص از کیف پول وندار سالن (مرحله‌ی ۳ چند درگاه، ۲۰۲۶-۰۹-۲۶). پاسخ‌ها عین نمونه‌ی رسمی وندار
 * (settlement/store: {status: 1, data: {settlement: [{id, transaction_id, amount (ریال), amount_toman, …}]}}).
 */
class VandarPayoutTest extends TestCase
{
    use RefreshDatabase;

    private const STORE_URL = 'https://api.vandar.io/v3/business/mahru_salon/settlement/store';

    private const REFRESH_URL = 'https://api.vandar.io/v3/refreshtoken';

    /** پاسخ بعد از فرستادن نرسید (cURL 28). «هرگز فرستاده نشد» (6/7) ناموفق ساده‌ست — App\Payments\HttpFailure. */
    private const TIMEOUT = 'cURL error 28: Operation timed out after 30001 milliseconds with 0 bytes received';

    private Salon $salon;

    protected function setUp(): void
    {
        parent::setUp();
        $this->salon = app(CurrentSalon::class)->get();
        $this->salon->paymentGateways()->delete();
    }

    private function vandar(array $credentials = []): SalonPaymentGateway
    {
        return $this->salon->paymentGateways()->create([
            'driver' => 'vandar', 'priority' => 5, 'is_active' => true,
            'credentials' => $credentials + ['api_key' => 'ipg-key', 'payout_business' => 'mahru_salon', 'payout_access_token' => 'ACCESS-1', 'payout_refresh_token' => 'REFRESH-1'],
        ]);
    }

    private static function settled(string $id = '406f20d0-397a-11ec-b752-6b667e3fe6ba', int $toman = 250000): array
    {
        return ['status' => 1, 'data' => ['settlement' => [[
            'id' => $id, 'transaction_id' => 163559577615, 'amount' => $toman * 10, 'amount_toman' => $toman,
            'wage_toman' => 0, 'status' => 'PENDING', 'track_id' => 'x',
        ]]]];
    }

    private function request(int $rial = 2500000, string $reference = '7'): PayoutRequest
    {
        return new PayoutRequest($rial, 'IR060180000000000000020600', 'تسویه حساب متخصص: مریم (درخواست #7)', $reference);
    }

    public function test_driver_posts_the_documented_settlement_in_toman_with_a_deterministic_track_id(): void
    {
        Http::fake([self::STORE_URL => Http::response(self::settled())]);

        $result = (new VandarPayoutDriver((array) $this->vandar()->credentials))->payout($this->request());

        $this->assertTrue($result->success);
        $this->assertSame('163559577615', $result->referenceCode);
        $this->assertSame('406f20d0-397a-11ec-b752-6b667e3fe6ba', $result->payoutId);
        Http::assertSent(fn (Request $r) => $r->url() === self::STORE_URL
            && $r->header('Authorization') === ['Bearer ACCESS-1']
            && $r['amount'] === 250000 // ۲٬۵۰۰٬۰۰۰ ریال = ۲۵۰٬۰۰۰ تومان
            && $r['iban'] === 'IR060180000000000000020600'
            && $r['track_id'] === 'mahru-wd-7'
            && ! isset($r['is_instant']));
    }

    public function test_amounts_below_the_vandar_minimum_are_refused_without_calling_vandar(): void
    {
        Http::fake();

        $result = (new VandarPayoutDriver((array) $this->vandar()->credentials))->payout($this->request(49990));

        $this->assertFalse($result->success);
        $this->assertFalse($result->unknown);
        $this->assertStringContainsString('۵٬۰۰۰ تومان', $result->message);
        Http::assertNothingSent();
    }

    public function test_an_expired_token_is_refreshed_both_tokens_are_saved_and_the_payout_is_retried_once(): void
    {
        $gateway = $this->vandar();
        Http::fake([
            self::STORE_URL => Http::sequence()->push(['message' => 'Unauthenticated.'], 401)->push(self::settled()),
            self::REFRESH_URL => Http::response(['token_type' => 'Bearer', 'expires_in' => 432000, 'access_token' => 'ACCESS-2', 'refresh_token' => 'REFRESH-2']),
        ]);

        $result = app(PayoutManager::class)->driver($gateway)->payout($this->request());

        $this->assertTrue($result->success);
        Http::assertSent(fn (Request $r) => $r->url() === self::REFRESH_URL && $r['refreshtoken'] === 'REFRESH-1');
        Http::assertSent(fn (Request $r) => $r->url() === self::STORE_URL && $r->header('Authorization') === ['Bearer ACCESS-2']);
        $stored = $gateway->fresh()->credentials;
        $this->assertSame('ACCESS-2', $stored['payout_access_token']);
        $this->assertSame('REFRESH-2', $stored['payout_refresh_token'], 'refresh_token قبلی دیگه معتبر نیست');
        $this->assertSame('ipg-key', $stored['api_key'], 'بقیه‌ی اطلاعات درگاه دست نخورده');
        Http::assertSentCount(3);
    }

    public function test_a_token_already_refreshed_by_someone_else_is_reused_instead_of_spending_the_refresh_token_again(): void
    {
        $gateway = $this->vandar();
        $staleDriver = app(PayoutManager::class)->driver($gateway);
        // کار زمان‌بندی‌شده در همین فاصله تمدید کرده:
        $gateway->update(['credentials' => ['payout_access_token' => 'ACCESS-NEW', 'payout_refresh_token' => 'REFRESH-NEW'] + $gateway->credentials]);
        Http::fake([
            self::STORE_URL => Http::sequence()->push([], 401)->push(self::settled()),
            self::REFRESH_URL => Http::response([], 500),
        ]);

        $this->assertTrue($staleDriver->payout($this->request())->success);
        Http::assertNotSent(fn (Request $r) => $r->url() === self::REFRESH_URL);
        Http::assertSent(fn (Request $r) => $r->url() === self::STORE_URL && $r->header('Authorization') === ['Bearer ACCESS-NEW']);
    }

    public function test_a_failed_refresh_is_a_clear_failure(): void
    {
        $gateway = $this->vandar();
        Http::fake([self::STORE_URL => Http::response([], 401), self::REFRESH_URL => Http::response(['message' => 'invalid refresh token'], 401)]);

        $result = app(PayoutManager::class)->driver($gateway)->payout($this->request());

        $this->assertFalse($result->success);
        $this->assertFalse($result->unknown, '۴۰۱ یعنی چیزی ثبت نشد');
        $this->assertStringContainsString('توکن‌های تازه', $result->message);
        $this->assertSame('ACCESS-1', $gateway->fresh()->credentials['payout_access_token']);
    }

    public function test_a_lost_answer_is_unknown_not_failed_and_is_never_retried(): void
    {
        foreach ([fn () => Http::failedConnection(self::TIMEOUT), fn () => Http::response(['message' => 'Server Error'], 502)] as $answer) {
            Http::swap(new \Illuminate\Http\Client\Factory);
            Http::fake([self::STORE_URL => $answer()]);

            $result = (new VandarPayoutDriver((array) $this->vandar()->credentials))->payout($this->request());

            $this->assertFalse($result->success);
            $this->assertTrue($result->unknown);
            $this->assertStringContainsString('mahru-wd-7', $result->message);
            Http::assertSentCount(1);
        }
    }

    public function test_a_request_that_never_left_the_server_is_a_plain_failure(): void
    {
        foreach (['cURL error 6: Could not resolve host: api.vandar.io', 'cURL error 7: Failed to connect to api.vandar.io port 443'] as $error) {
            Http::swap(new \Illuminate\Http\Client\Factory);
            Http::fake([self::STORE_URL => Http::failedConnection($error)]);

            $result = (new VandarPayoutDriver((array) $this->vandar()->credentials))->payout($this->request());

            $this->assertFalse($result->success);
            $this->assertFalse($result->unknown, 'وندار چیزی ندیده — برگشت به کیف پول متخصص امنه');
        }
    }

    public function test_a_rejected_settlement_reports_vandars_reason(): void
    {
        Http::fake([self::STORE_URL => Http::response(['status' => 0, 'errors' => ['amount' => ['موجودی کیف پول کافی نیست']]], 422)]);

        $result = (new VandarPayoutDriver((array) $this->vandar()->credentials))->payout($this->request());

        $this->assertFalse($result->success);
        $this->assertFalse($result->unknown);
        $this->assertStringContainsString('موجودی کیف پول کافی نیست', $result->message);
    }

    // ── مسیر کامل برداشت ──

    private function processingWithdrawal(int $amountToman = 250000): WithdrawalRequest
    {
        $specialist = Specialist::factory()->create();
        $wallet = $specialist->getOrCreateWallet();
        $wallet->update(['balance' => 500000 - $amountToman, 'total_withdrawn' => $amountToman, 'iban' => 'IR060180000000000000020600']);

        return WithdrawalRequest::create([
            'wallet_id' => $wallet->id, 'specialist_id' => $specialist->id, 'amount' => $amountToman, 'fee' => 0,
            'net_amount' => $amountToman, 'method' => 'iban', 'iban' => $wallet->iban, 'account_holder_name' => 'مریم', 'status' => 'processing',
        ]);
    }

    public function test_a_withdrawal_is_paid_from_the_salons_vandar_wallet(): void
    {
        Event::fake([WithdrawalApproved::class]);
        $this->vandar();
        $this->assertTrue($this->salon->fresh()->canAutoPayout());
        Http::fake([self::STORE_URL => Http::response(self::settled())]);
        $withdrawal = $this->processingWithdrawal();

        (new ProcessWithdrawalJob($withdrawal->id))->handle(app(SalonPayoutService::class));

        $withdrawal->refresh();
        $this->assertSame('completed', $withdrawal->status);
        $this->assertSame('163559577615', $withdrawal->reference_code);
        Http::assertSent(fn (Request $r) => $r['amount'] === 250000 && $r['track_id'] === 'mahru-wd-'.$withdrawal->id);
        Event::assertDispatched(WithdrawalApproved::class);
    }

    public function test_an_unknown_outcome_keeps_the_withdrawal_processing_and_does_not_refund_the_wallet(): void
    {
        Event::fake([WithdrawalRejected::class, WithdrawalApproved::class]);
        $this->vandar();
        Http::fake([self::STORE_URL => Http::failedConnection(self::TIMEOUT)]);
        $withdrawal = $this->processingWithdrawal();

        (new ProcessWithdrawalJob($withdrawal->id))->handle(app(SalonPayoutService::class));

        $withdrawal->refresh();
        $this->assertSame('processing', $withdrawal->status);
        $this->assertTrue($withdrawal->payment_details['needs_manual_check']);
        $this->assertSame('mahru-wd-'.$withdrawal->id, $withdrawal->payment_details['payout_raw']['track_id']);
        $this->assertSame(250000.0, (float) $withdrawal->wallet->fresh()->balance, 'پول به کیف پول برنگشت (ممکنه واریز شده باشه)');
        $this->assertDatabaseMissing('wallet_transactions', ['wallet_id' => $withdrawal->wallet_id, 'type' => 'refund']);
        Event::assertNotDispatched(WithdrawalRejected::class);
        Event::assertNotDispatched(WithdrawalApproved::class);

        // صف دوباره نمی‌فرسته — مدیر باید دستی تأیید یا رد کنه
        $this->assertFalse(app(\App\Services\Admin\Wallet\WalletAdminService::class)->autoPayout($withdrawal)['success']);
    }

    public function test_incomplete_vandar_payout_details_do_not_enable_auto_payout(): void
    {
        $gateway = $this->vandar(['payout_refresh_token' => '']);

        $this->assertFalse(app(PayoutManager::class)->isConfigured($gateway));
        $this->assertFalse($this->salon->fresh()->canAutoPayout());
    }

    // ── تمدید روزانه ──

    public function test_the_daily_command_refreshes_every_configured_vandar_gateway(): void
    {
        $configured = $this->vandar();
        $incomplete = $this->vandar(['payout_refresh_token' => '']);
        $otherSalon = Salon::factory()->create()->paymentGateways()->create(['driver' => 'vandar', 'priority' => 1, 'credentials' => ['api_key' => 'k', 'payout_business' => 'other', 'payout_access_token' => 'O-ACCESS', 'payout_refresh_token' => 'O-REFRESH']]);
        Http::fake([self::REFRESH_URL => fn (Request $r) => Http::response(['access_token' => 'NEW-'.$r['refreshtoken'], 'refresh_token' => 'NEXT-'.$r['refreshtoken'], 'expires_in' => 432000])]);

        $this->artisan('payouts:refresh-vandar-tokens')->expectsOutputToContain('2 تمدید شد')->assertSuccessful();

        $this->assertSame('NEW-REFRESH-1', $configured->fresh()->credentials['payout_access_token']);
        $this->assertSame('NEXT-O-REFRESH', $otherSalon->fresh()->credentials['payout_refresh_token'], 'همه‌ی سالن‌ها');
        $this->assertSame('ACCESS-1', $incomplete->fresh()->credentials['payout_access_token']);
        Http::assertSentCount(2);
    }

    public function test_owner_enters_vandar_payout_details_and_tokens_stay_hidden(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $gateway = $this->salon->paymentGateways()->create(['driver' => 'vandar', 'priority' => 1, 'credentials' => ['api_key' => 'ipg-key']]);

        $this->actingAs($owner)->put(route('admin.payment-gateways.update', $gateway->id), [
            'credentials' => ['payout_business' => 'bad name!', 'payout_access_token' => 'A', 'payout_refresh_token' => 'R'], 'is_active' => '1',
        ])->assertSessionHasErrors('credentials.payout_business');

        $this->actingAs($owner)->put(route('admin.payment-gateways.update', $gateway->id), [
            'credentials' => ['payout_business' => 'mahru_salon', 'payout_access_token' => 'PANEL-ACCESS', 'payout_refresh_token' => 'PANEL-REFRESH'], 'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertSame('ipg-key', $gateway->fresh()->credentials['api_key'], 'کلید درگاه با فیلد خالی حفظ شد');
        $this->assertTrue($this->salon->fresh()->canAutoPayout());
        $this->actingAs($owner)->get(route('admin.payment-gateways.index'))->assertDontSee('PANEL-ACCESS')->assertDontSee('PANEL-REFRESH');

        // ویرایش بعدی بدون دست زدن به توکن‌ها (مثلاً بعد از تمدید خودکار) توکن‌های ذخیره‌شده رو پاک نمی‌کنه
        $gateway->update(['credentials' => ['payout_access_token' => 'AUTO-REFRESHED'] + $gateway->fresh()->credentials]);
        $this->actingAs($owner)->put(route('admin.payment-gateways.update', $gateway->id), [
            'credentials' => ['payout_business' => 'mahru_salon'], 'is_active' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertSame('AUTO-REFRESHED', $gateway->fresh()->credentials['payout_access_token']);
    }
}
