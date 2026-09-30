<?php

namespace Tests\Feature\Wallet;

use App\Jobs\ProcessWithdrawalJob;
use App\Models\SpecialistWallet;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Services\Payment\SalonPayoutService;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * تصمیم ۲۰۲۶-۰۹-۳۰: تسویه‌ی خودکار فقط به شبایی که مدیر تأیید کرده و همان شبای درخواست برداشت است.
 */
class AutoPayoutRequiresVerifiedIbanTest extends TestCase
{
    use RefreshDatabase;

    private const IBAN = 'IR820540102680020817909002';

    private const OTHER_IBAN = 'IR062960000000100324200001';

    protected function setUp(): void
    {
        parent::setUp();
        app(CurrentSalon::class)->get()->update(['zarinpal_payout_api_key' => str_repeat('t', 40)]);
    }

    private function withdrawal(bool $verified, string $walletIban = self::IBAN, string $status = 'pending'): WithdrawalRequest
    {
        $wallet = SpecialistWallet::factory()->create([
            'iban' => $walletIban,
            'iban_verified' => $verified,
            'balance' => 0,
        ]);

        return WithdrawalRequest::factory()->create([
            'wallet_id' => $wallet->id,
            'specialist_id' => $wallet->specialist_id,
            'iban' => self::IBAN,
            'status' => $status,
        ]);
    }

    private function autoPayout(WithdrawalRequest $withdrawal)
    {
        return $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->post("/admin/wallet/withdrawals/{$withdrawal->id}/auto-payout");
    }

    public function test_auto_payout_is_refused_for_an_unverified_iban(): void
    {
        Queue::fake();
        $withdrawal = $this->withdrawal(verified: false);

        $this->autoPayout($withdrawal)->assertSessionHas('error');

        $this->assertStringContainsString('تأیید', session('error'));
        $this->assertSame('pending', $withdrawal->fresh()->status);
        Queue::assertNotPushed(ProcessWithdrawalJob::class);
    }

    public function test_auto_payout_is_refused_when_the_request_iban_is_not_the_wallets_current_iban(): void
    {
        Queue::fake();
        $withdrawal = $this->withdrawal(verified: true, walletIban: self::OTHER_IBAN);

        $this->autoPayout($withdrawal)->assertSessionHas('error');

        $this->assertSame('pending', $withdrawal->fresh()->status);
        Queue::assertNotPushed(ProcessWithdrawalJob::class);
    }

    public function test_auto_payout_goes_ahead_for_the_verified_iban(): void
    {
        Queue::fake();
        $withdrawal = $this->withdrawal(verified: true);

        $this->autoPayout($withdrawal)->assertSessionHas('success');

        $this->assertSame('processing', $withdrawal->fresh()->status);
        Queue::assertPushed(ProcessWithdrawalJob::class);
    }

    public function test_the_job_sends_nothing_when_verification_was_lost_after_dispatch(): void
    {
        $withdrawal = $this->withdrawal(verified: false, status: 'processing');

        $this->mock(SalonPayoutService::class, fn ($mock) => $mock->shouldNotReceive('payout'));

        (new ProcessWithdrawalJob($withdrawal->id))->handle(app(SalonPayoutService::class));

        $fresh = $withdrawal->fresh();
        $this->assertSame('pending', $fresh->status);
        $this->assertNotEmpty($fresh->payment_details['auto_payout_blocked'] ?? null);
    }

    public function test_the_withdrawal_page_disables_auto_payout_and_says_why(): void
    {
        $withdrawal = $this->withdrawal(verified: false);

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get("/admin/wallet/withdrawals/{$withdrawal->id}")
            ->assertOk()
            ->assertDontSee(route('admin.wallet.withdrawals.auto-payout', $withdrawal))
            ->assertSee('شبای متخصص هنوز تأیید نشده');
    }
}
