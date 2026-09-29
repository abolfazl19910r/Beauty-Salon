<?php

namespace Tests\Feature\Notification;

use App\Jobs\ProcessWithdrawalJob;
use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\NotificationSetting;
use App\Models\PaymentTransaction;
use App\Models\Role;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Notifications\Admin\Attention\AttentionRequiredNotification;
use App\Services\Payment\SalonPayoutService;
use App\Support\CurrentSalon;
use App\Support\Notifications\NotificationEvents;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Sleep;
use Tests\TestCase;

/**
 * اعلان به مالک سالن وقتی یک پرداخت یا تسویه تازه وارد «نیاز به بررسی» می‌شود (۲۰۲۶-۰۹-۳۰): فقط مالک‌های همان سالن،
 * طبق تنظیمات اطلاع‌رسانی همان سالن (پیش‌فرض: داخلی + پیامک)، و برای هر مورد فقط یک بار.
 */
class AttentionRequiredNotificationTest extends TestCase
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

    private function abandonedPayment(array $attributes = []): PaymentTransaction
    {
        $tx = PaymentTransaction::create($attributes + [
            'salon_id' => $this->salon->id, 'driver' => 'zibal', 'purpose' => 'booking', 'amount_rial' => 605000,
            'token' => (string) random_int(1000, 999999), 'callback_url' => 'x', 'status' => 'failed',
            'verify_response' => ['unanswered' => true],
        ]);
        PaymentTransaction::whereKey($tx->id)->toBase()->update(['updated_at' => now()->subHours(25)]);

        return $tx->fresh();
    }

    public function test_a_payment_flagged_by_reconcile_notifies_only_this_salons_owners_once(): void
    {
        Http::fake(['*' => Http::failedConnection('cURL error 28: Operation timed out')]);

        $staff = User::factory()->create(['is_admin' => false]);
        $this->salon->admins()->attach($staff->id, ['role' => 'staff']);
        $staff->roles()->attach(Role::where('name', 'staff')->value('id'));

        $other = Salon::factory()->create(['slug' => 'other-attention']);
        $otherOwner = User::factory()->create(['is_admin' => true]);
        $otherOwner->salons()->detach();
        $other->admins()->attach($otherOwner->id, ['role' => 'owner']);

        $tx = $this->abandonedPayment();

        $this->artisan('payments:reconcile')->assertSuccessful();
        $this->artisan('payments:reconcile')->assertSuccessful();

        $this->assertTrue($tx->fresh()->needs_attention);
        Notification::assertSentToTimes($this->owner, AttentionRequiredNotification::class, 1);
        Notification::assertSentTo($this->owner, AttentionRequiredNotification::class,
            fn (AttentionRequiredNotification $n) => $n->kind === 'payment' && $n->recordId === $tx->id && $n->salonId === $this->salon->id);
        Notification::assertNotSentTo($staff, AttentionRequiredNotification::class);
        Notification::assertNotSentTo($otherOwner, AttentionRequiredNotification::class);
    }

    public function test_an_asan_payment_verified_but_not_settled_notifies_the_owner(): void
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

        $this->assertTrue($tx->fresh()->needs_attention);
        Notification::assertSentToTimes($this->owner, AttentionRequiredNotification::class, 1);
    }

    private function unknownPayoutWithdrawal(): WithdrawalRequest
    {
        $this->salon->paymentGateways()->create(['driver' => 'zibal', 'priority' => 1, 'credentials' => ['merchant' => 'z', 'payout_access_token' => 't', 'payout_wallet_id' => '1']]);
        $specialist = Specialist::factory()->create(['name' => 'مریم']);
        $wallet = $specialist->getOrCreateWallet();
        $wallet->update(['balance' => 250000, 'total_withdrawn' => 250000, 'iban' => 'IR060180000000000000020600']);

        return WithdrawalRequest::create([
            'wallet_id' => $wallet->id, 'specialist_id' => $specialist->id, 'amount' => 250000, 'fee' => 0, 'net_amount' => 250000,
            'method' => 'iban', 'iban' => $wallet->iban, 'account_holder_name' => 'مریم', 'status' => 'processing',
        ]);
    }

    public function test_an_unknown_payout_notifies_the_owner_once_even_if_the_job_runs_again(): void
    {
        $withdrawal = $this->unknownPayoutWithdrawal();
        Http::fake(['api.zibal.ir/*' => Http::failedConnection('cURL error 28: Operation timed out after 30001 milliseconds')]);

        app(CurrentSalon::class)->clear(); // مثل worker صف: بدون سالن جاری
        (new ProcessWithdrawalJob($withdrawal->id))->handle(app(SalonPayoutService::class));
        (new ProcessWithdrawalJob($withdrawal->id))->handle(app(SalonPayoutService::class));
        app(CurrentSalon::class)->set($this->salon);

        $this->assertTrue($withdrawal->fresh()->needs_manual_check);
        Notification::assertSentToTimes($this->owner, AttentionRequiredNotification::class, 1);
        Notification::assertSentTo($this->owner, AttentionRequiredNotification::class,
            fn (AttentionRequiredNotification $n) => $n->kind === 'withdrawal' && $n->recordId === $withdrawal->id && $n->salonId === $this->salon->id);
    }

    public function test_channels_default_to_in_app_and_sms_and_follow_the_records_salon_settings(): void
    {
        $tx = $this->abandonedPayment();
        $notification = new AttentionRequiredNotification('payment', $tx->id, $this->salon->id);

        $this->assertSame(['database', 'sms'], $notification->via($this->owner));

        // مالک دو سالن: تنظیمات سالنِ خود پرداخت ملاک است، نه اولین سالن مالک.
        $second = Salon::factory()->create(['slug' => 'second-attention']);
        $second->admins()->attach($this->owner->id, ['role' => 'owner']);
        NotificationSetting::create([
            'salon_id' => $second->id, 'event_key' => NotificationEvents::PAYMENT_ATTENTION_ADMIN,
            'sms_enabled' => false, 'database_enabled' => true, 'telegram_enabled' => false,
        ]);
        $secondTx = $this->abandonedPayment(['salon_id' => $second->id]);

        $this->assertSame(['database'], (new AttentionRequiredNotification('payment', $secondTx->id, $second->id))->via($this->owner));
        $this->assertSame(['database', 'sms'], $notification->via($this->owner), 'سالن اول دست نخورده');
    }

    public function test_the_messages_name_the_salon_amount_and_where_to_act(): void
    {
        $tx = $this->abandonedPayment();
        $payment = new AttentionRequiredNotification('payment', $tx->id, $this->salon->id);

        $data = $payment->toArray($this->owner);
        $this->assertSame(route('admin.payment-attention.index', [], false), $data['link']);
        $this->assertStringContainsString('60,500', $data['message']);
        $this->assertStringContainsString($this->salon->name, $payment->smsText());
        $this->assertStringContainsString('60,500 تومان', $payment->smsText());

        $withdrawal = $this->unknownPayoutWithdrawal();
        $payout = new AttentionRequiredNotification('withdrawal', $withdrawal->id, $this->salon->id);
        $this->assertSame(route('admin.wallet.withdrawals.show', $withdrawal->id, false), $payout->toArray($this->owner)['link']);
        $this->assertStringContainsString('مریم', $payout->smsText());
        $this->assertStringContainsString('250,000 تومان', $payout->smsText());
    }
}
