<?php

namespace Tests\Feature\Payment;

use App\Jobs\ProcessWithdrawalJob;
use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\PaymentTransaction;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Notifications\Payment\PaymentRefundedNotification;
use App\Services\Payment\SalonPayoutService;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Sleep;
use Tests\TestCase;

/**
 * ⭐ «نیاز به بررسی انسانی» (۲۰۲۶-۰۹-۲۶): پرداخت‌ها و تسویه‌هایی که پیگیری خودکار به نتیجه نرسید، حالا در پنل مدیریت
 * دیده می‌شن (قبلاً فقط در لاگ و جزئیات برداشت): پرچم needs_attention / needs_manual_check، صفحه‌ی «پرداخت‌های
 * نیازمند بررسی» (واریز به کیف پول مشتری یا «رسیدگی شد»)، فیلتر و بنر برداشت‌ها، و هشدار داشبورد و منو.
 */
class PaymentAttentionTest extends TestCase
{
    use RefreshDatabase;

    private Salon $salon;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        Sleep::fake();
        Notification::fake();
        $this->salon = app(CurrentSalon::class)->get();
        $this->salon->paymentGateways()->delete();
        $this->owner = User::factory()->create(['is_admin' => true]);
    }

    private function tx(array $attributes = []): PaymentTransaction
    {
        return PaymentTransaction::create($attributes + [
            'salon_id' => $this->salon->id, 'driver' => 'zibal', 'purpose' => 'booking', 'amount_rial' => 605000,
            'token' => (string) random_int(1000, 999999), 'callback_url' => 'x', 'status' => 'failed',
            'verify_response' => ['unanswered' => true],
        ]);
    }

    /** updated_at رو بدون تغییر بقیه‌ی ردیف به گذشته می‌بره. */
    private function lastTouched(PaymentTransaction $tx, \DateTimeInterface $at): PaymentTransaction
    {
        PaymentTransaction::whereKey($tx->id)->toBase()->update(['updated_at' => $at]);

        return $tx->fresh();
    }

    // ── پرچم‌گذاری در payments:reconcile ──

    public function test_reconcile_flags_payments_only_after_their_automatic_window_ends(): void
    {
        Http::fake(['*' => Http::failedConnection('cURL error 28: Operation timed out')]);
        $indirectExpired = $this->lastTouched($this->tx(), now()->subHours(25));
        $indirectInWindow = $this->lastTouched($this->tx(), now()->subHours(23));
        $samanExpired = $this->lastTouched($this->tx(['driver' => 'saman', 'gateway_receipt' => 'R1']), now()->subMinutes(50));
        $stuckReconciling = $this->lastTouched($this->tx(['driver' => 'zarinpal', 'status' => 'reconciling']), now()->subHours(26));
        $plainFailure = $this->lastTouched($this->tx(['verify_response' => ['status' => 51]]), now()->subHours(30));
        $alreadyResolved = $this->lastTouched($this->tx(['verify_response' => ['unanswered' => false, 'manual_resolution' => ['action' => 'resolved']]]), now()->subHours(30));
        $weeksOld = $this->lastTouched($this->tx(), now()->subDays(10));

        $this->artisan('payments:reconcile')->expectsOutputToContain('نیاز به بررسی دستی: 3')->assertSuccessful();

        $this->assertTrue($indirectExpired->fresh()->needs_attention);
        $this->assertTrue($samanExpired->fresh()->needs_attention, 'مهلت برگشت سامان (۴۵ دقیقه) گذشت و برگشت نشد');
        $this->assertTrue($stuckReconciling->fresh()->needs_attention, 'فرایند وسط کار متوقف شده');
        $this->assertFalse($indirectInWindow->fresh()->needs_attention, 'هنوز reconcile داره می‌پرسه');
        $this->assertFalse($plainFailure->fresh()->needs_attention, 'پرداخت ناموفق عادی کاری نمی‌خواد');
        $this->assertFalse($alreadyResolved->fresh()->needs_attention, 'رسیدگی‌شده دوباره پرچم نمی‌خوره');
        $this->assertFalse($weeksOld->fresh()->needs_attention, 'فقط یک هفته بعد از هر بازه پرسیده می‌شه');
        $this->assertEquals(now()->subHours(25)->timestamp, $indirectExpired->fresh()->updated_at->timestamp, 'پرچم‌گذاری updated_at رو تغییر نمی‌ده');
    }

    public function test_an_asan_payment_verified_but_not_settled_is_paid_and_flagged(): void
    {
        $this->salon->paymentGateways()->create(['driver' => 'asanpardakht', 'credentials' => ['merchant_config_id' => '99', 'username' => 'u', 'password' => 'p'], 'priority' => 1]);
        Http::fake([
            'ipgrest.asanpardakht.ir/v1/Time' => Http::response('"20260926 101500"'),
            'ipgrest.asanpardakht.ir/v1/Token' => Http::response('"REF-S"'),
            'ipgrest.asanpardakht.ir/v1/TranResult*' => Http::response(['payGateTranID' => 31, 'rrn' => 'RRN', 'amount' => 600000]),
            'ipgrest.asanpardakht.ir/v1/Verify' => Http::response('', 200),
            'ipgrest.asanpardakht.ir/v1/Settlement' => Http::response('', 503),
        ]);
        $user = User::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id, 'service_id' => BeautyService::factory()->create()->id, 'specialist_id' => Specialist::factory()->create()->id,
            'payment_status' => 'unpaid', 'status' => 'pending_payment', 'prepayment_amount' => 60000,
        ]);

        $this->actingAs($user)->post(route('payment.process', $booking));
        $tx = PaymentTransaction::where('payable_id', $booking->id)->sole();
        $this->app['auth']->forgetGuards();
        $this->actingAs($user)->get($this->post("/payments/return/{$tx->public_id}", ['ReturningParams' => 'x'])->headers->get('Location'));

        $this->assertSame('paid', $booking->fresh()->payment_status, 'مشتری نوبتش رو داره');
        $this->assertTrue($tx->fresh()->needs_attention);
        $this->actingAs($this->owner)->get(route('admin.payment-attention.index'))->assertSee('Settlement')->assertDontSee('value="wallet_credit"', false);
    }

    // ── صفحه‌ی پرداخت‌های نیازمند بررسی ──

    public function test_the_page_lists_only_this_salons_flagged_payments_and_only_for_owners(): void
    {
        $customer = User::factory()->create(['name' => 'مشتری-الف', 'phone' => '09121110000']);
        $this->tx(['needs_attention' => true, 'user_id' => $customer->id, 'token' => 'TOKEN-OWN']);
        $this->tx(['needs_attention' => false, 'token' => 'TOKEN-NOT-FLAGGED']);
        $other = Salon::factory()->create(['slug' => 'other-attention']);
        $this->tx(['needs_attention' => true, 'salon_id' => $other->id, 'token' => 'TOKEN-OTHER-SALON']);

        $this->actingAs($this->owner)->get(route('admin.payment-attention.index'))->assertOk()
            ->assertSee('TOKEN-OWN')->assertSee('مشتری-الف')->assertSee('60,500')->assertSee('پیش‌پرداخت نوبت')
            ->assertDontSee('TOKEN-NOT-FLAGGED')->assertDontSee('TOKEN-OTHER-SALON');

        $staff = User::factory()->create(['is_admin' => true]);
        $this->salon->admins()->attach($staff->id, ['role' => 'staff']);
        $this->actingAs($staff)->get(route('admin.payment-attention.index'))->assertForbidden();
    }

    public function test_crediting_the_customer_wallet_after_checking_the_gateway_panel(): void
    {
        $customer = User::factory()->create(['phone' => '09121234567']);
        $booking = Booking::factory()->create(['user_id' => $customer->id, 'status' => 'cancelled', 'payment_status' => 'unpaid']);
        $tx = $this->tx(['needs_attention' => true, 'user_id' => $customer->id, 'payable_type' => Booking::class, 'payable_id' => $booking->id]);

        $this->actingAs($this->owner)->post(route('admin.payment-attention.resolve', $tx->id), ['action' => 'wallet_credit', 'note' => ''])
            ->assertSessionHasErrors('note');

        $this->actingAs($this->owner)->post(route('admin.payment-attention.resolve', $tx->id), ['action' => 'wallet_credit', 'note' => 'در پنل زیبال پرداخت‌شده بود'])
            ->assertSessionHas('success');

        $tx->refresh();
        $this->assertSame('refunded', $tx->status);
        $this->assertFalse($tx->needs_attention);
        $this->assertSame('manual', $tx->verify_response['recovery_via']);
        $this->assertSame('در پنل زیبال پرداخت‌شده بود', $tx->verify_response['manual_resolution']['note']);
        $this->assertSame($this->owner->id, $tx->verify_response['manual_resolution']['by']);
        $this->assertSame(60500.0, (float) $customer->getOrCreateWallet()->fresh()->balance);
        Notification::assertSentTo($customer, PaymentRefundedNotification::class, fn ($n) => $n->walletToman === 60500 && $n->bookingId === $booking->id);

        // دوبار کلیک: دیگه نه واریز، نه پیامک
        $this->actingAs($this->owner)->post(route('admin.payment-attention.resolve', $tx->id), ['action' => 'wallet_credit', 'note' => 'دوباره'])->assertNotFound();
        $this->assertSame(60500.0, (float) $customer->getOrCreateWallet()->fresh()->balance);
        Notification::assertSentToTimes($customer, PaymentRefundedNotification::class, 1);
    }

    public function test_two_simultaneous_credit_clicks_credit_once(): void
    {
        $customer = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $customer->id, 'status' => 'cancelled', 'payment_status' => 'unpaid']);
        $tx = $this->tx(['needs_attention' => true, 'user_id' => $customer->id, 'payable_type' => Booking::class, 'payable_id' => $booking->id]);
        $secondClick = $tx->fresh(); // هر دو درخواست ردیف رو قبل از ثبت هیچ‌کدوم خونده بودن
        $recovery = app(\App\Services\Payment\UnansweredVerifyRecovery::class);

        $this->assertTrue($recovery->creditManually($tx, $this->owner, 'اول'));
        $this->assertFalse($recovery->creditManually($secondClick, $this->owner, 'دوم'));

        $this->assertSame(60500.0, (float) $customer->getOrCreateWallet()->fresh()->balance);
        Notification::assertSentToTimes($customer, PaymentRefundedNotification::class, 1);
    }

    public function test_marking_resolved_moves_no_money_and_stops_all_automation(): void
    {
        Http::fake();
        $customer = User::factory()->create();
        $stuck = $this->tx(['needs_attention' => true, 'user_id' => $customer->id, 'status' => 'reconciling']);

        $this->actingAs($this->owner)->post(route('admin.payment-attention.resolve', $stuck->id), ['action' => 'resolved', 'note' => 'درگاه به کارت برگردانده بود'])
            ->assertSessionHas('success');

        $stuck->refresh();
        $this->assertFalse($stuck->needs_attention);
        $this->assertSame('failed', $stuck->status);
        $this->assertFalse($stuck->verify_response['unanswered']);
        $this->assertSame('resolved', $stuck->verify_response['manual_resolution']['action']);
        $this->assertSame(0.0, (float) $customer->getOrCreateWallet()->fresh()->balance);

        $this->travel(1)->hours();
        $this->artisan('payments:reconcile');
        $this->assertFalse($stuck->fresh()->needs_attention, 'دوباره پرچم نمی‌خوره');
        Http::assertNothingSent();
        Notification::assertNothingSent();
    }

    public function test_another_salons_payment_cannot_be_resolved(): void
    {
        $other = $this->tx(['needs_attention' => true, 'salon_id' => Salon::factory()->create(['slug' => 'other-resolve'])->id, 'user_id' => User::factory()->create()->id]);

        $this->actingAs($this->owner)->post(route('admin.payment-attention.resolve', $other->id), ['action' => 'wallet_credit', 'note' => 'تلاش'])->assertNotFound();
        $this->assertTrue($other->fresh()->needs_attention);
    }

    // ── برداشت‌ها، منو و داشبورد ──

    private function unknownPayoutWithdrawal(): WithdrawalRequest
    {
        $this->salon->paymentGateways()->create(['driver' => 'zibal', 'priority' => 1, 'credentials' => ['merchant' => 'z', 'payout_access_token' => 't', 'payout_wallet_id' => '1']]);
        $specialist = Specialist::factory()->create();
        $wallet = $specialist->getOrCreateWallet();
        $wallet->update(['balance' => 250000, 'total_withdrawn' => 250000, 'iban' => 'IR060180000000000000020600']);
        $withdrawal = WithdrawalRequest::create([
            'wallet_id' => $wallet->id, 'specialist_id' => $specialist->id, 'amount' => 250000, 'fee' => 0, 'net_amount' => 250000,
            'method' => 'iban', 'iban' => $wallet->iban, 'account_holder_name' => 'مریم', 'status' => 'processing',
        ]);
        Http::fake(['api.zibal.ir/*' => Http::failedConnection('cURL error 28: Operation timed out after 30001 milliseconds')]);
        (new ProcessWithdrawalJob($withdrawal->id))->handle(app(SalonPayoutService::class));

        return $withdrawal->fresh();
    }

    public function test_an_unknown_payout_is_flagged_filtered_explained_and_cleared_by_a_manual_decision(): void
    {
        $withdrawal = $this->unknownPayoutWithdrawal();
        $this->assertTrue($withdrawal->needs_manual_check);
        $this->assertSame('processing', $withdrawal->status);

        $this->actingAs($this->owner)->get(route('admin.wallet.withdrawals'))->assertSee('منتظر بررسی دستی شماست')->assertSee('بررسی دستی');
        $filtered = $this->actingAs($this->owner)->get(route('admin.wallet.withdrawals', ['needs_check' => 1]));
        $this->assertSame([$withdrawal->id], $filtered->viewData('withdrawals')->pluck('id')->all());
        $this->actingAs($this->owner)->get(route('admin.wallet.withdrawals.show', $withdrawal))->assertSee('نتیجه‌ی تسویه‌ی خودکار نامعلوم است')->assertSee('#'.$withdrawal->id);

        $this->actingAs($this->owner)->put(route('admin.wallet.withdrawals.approve', $withdrawal), ['payment_reference' => 'BANK-REF-1'])->assertSessionHasNoErrors();

        $withdrawal->refresh();
        $this->assertSame('completed', $withdrawal->status);
        $this->assertFalse($withdrawal->needs_manual_check);
        $this->assertArrayHasKey('unknown_payout', $withdrawal->payment_details, 'سابقه‌ی تسویه‌ی نامعلوم کنار تایید دستی می‌مونه');
        $this->actingAs($this->owner)->get(route('admin.wallet.withdrawals'))->assertDontSee('منتظر بررسی دستی شماست');
    }

    public function test_rejecting_an_unknown_payout_clears_the_flag_and_refunds_the_specialist(): void
    {
        $withdrawal = $this->unknownPayoutWithdrawal();

        $this->actingAs($this->owner)->put(route('admin.wallet.withdrawals.reject', $withdrawal), ['reason' => 'در پنل زیبال واریزی نبود']);

        $withdrawal->refresh();
        $this->assertSame('failed', $withdrawal->status);
        $this->assertFalse($withdrawal->needs_manual_check);
        $this->assertSame(500000.0, (float) $withdrawal->wallet->fresh()->balance);
    }

    public function test_the_dashboard_and_menu_point_the_owner_to_what_needs_attention(): void
    {
        $this->actingAs($this->owner)->get(route('admin.dashboard'))->assertOk()->assertDontSee('data-attention-alert', false);

        $this->tx(['needs_attention' => true]);
        $this->unknownPayoutWithdrawal();

        $this->actingAs($this->owner)->get(route('admin.dashboard'))->assertOk()
            ->assertSee('data-attention-alert', false)
            ->assertSee('پرداخت مشتری که پاسخ درگاهش نرسید')
            ->assertSee('تسویه‌ی متخصص با نتیجه‌ی نامعلوم')
            ->assertSee(route('admin.payment-attention.index'), false);

        $other = Salon::factory()->create(['slug' => 'other-dashboard']);
        $this->tx(['needs_attention' => true, 'salon_id' => $other->id]);
        $this->assertSame(1, app(\App\Services\Admin\AttentionCounts::class)->payments(), 'شمارش فقط سالن فعلی');

        app(CurrentSalon::class)->set($other);
        $otherSpecialist = Specialist::factory()->create();
        WithdrawalRequest::create([
            'wallet_id' => $otherSpecialist->getOrCreateWallet()->id, 'specialist_id' => $otherSpecialist->id, 'amount' => 1, 'fee' => 0, 'net_amount' => 1,
            'method' => 'iban', 'iban' => 'IR1', 'account_holder_name' => 'x', 'status' => 'processing', 'needs_manual_check' => true,
        ]);
        app(CurrentSalon::class)->set($this->salon);
        $this->assertSame(1, app(\App\Services\Admin\AttentionCounts::class)->withdrawals(), 'برداشت سالن دیگه شمرده نمی‌شه');
    }
}
