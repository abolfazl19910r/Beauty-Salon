<?php

namespace Tests\Feature\Admin;

use App\Models\Salon;
use App\Models\Specialist;
use App\Models\SpecialistWallet;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ نشت بین سالن‌ها (۲۰۲۶-۰۹-۲۶، با probe بازتولید شد): فهرست و آمار «درخواست‌های برداشت» و «کیف پول‌ها»ی مدیریت،
 * رکوردهای سالن‌های دیگه رو هم نشون می‌داد (مبلغ، تاریخ، شناسه؛ و جمع موجودی/درآمد همه‌ی سالن‌ها). جدول‌ها salon_id
 * ندارن؛ حالا از طریق متخصصِ سالن فعلی فیلتر می‌شن. صفحه‌ی جزئیات از قبل با CrossSalonImplicitBindingTest پوشش داشت.
 */
class WalletListSalonScopeTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create(['is_admin' => true]);
    }

    /** @return array{0: SpecialistWallet, 1: WithdrawalRequest} */
    private function specialistWithWithdrawal(?Salon $salon, string $name, float $balance, int $amount): array
    {
        if ($salon) {
            app(CurrentSalon::class)->set($salon);
        }
        $specialist = Specialist::factory()->create(['name' => $name]);
        $wallet = SpecialistWallet::factory()->for($specialist)->create(['balance' => $balance, 'total_earned' => $balance, 'total_withdrawn' => 0, 'pending_amount' => 0]);
        $withdrawal = WithdrawalRequest::factory()->create(['specialist_id' => $specialist->id, 'wallet_id' => $wallet->id, 'amount' => $amount, 'status' => 'pending']);
        if ($salon) {
            app(CurrentSalon::class)->clear();
        }

        return [$wallet, $withdrawal];
    }

    public function test_withdrawal_list_and_stats_show_only_this_salons_specialists(): void
    {
        $this->specialistWithWithdrawal(null, 'OWN-SPECIALIST', 100000, 111111);
        $this->specialistWithWithdrawal(Salon::factory()->create(['slug' => 'other-wl']), 'OTHER-SPECIALIST', 900000, 777777);

        $response = $this->actingAs($this->owner)->get(route('admin.wallet.withdrawals'))->assertOk();

        $response->assertSee('111,111')->assertDontSee('777,777');
        $this->assertSame(1, $response->viewData('pendingCount'));
        $this->assertEquals(111111, $response->viewData('pendingAmount'));
        $this->assertSame(1, $response->viewData('withdrawals')->total());
    }

    public function test_wallet_list_and_totals_show_only_this_salons_specialists(): void
    {
        $this->specialistWithWithdrawal(null, 'OWN-SPECIALIST', 100000, 1);
        $this->specialistWithWithdrawal(Salon::factory()->create(['slug' => 'other-wl2']), 'OTHER-SPECIALIST', 900000, 1);

        $response = $this->actingAs($this->owner)->get(route('admin.wallet.index'))->assertOk();

        $this->assertEquals(100000, $response->viewData('totalBalance'));
        $this->assertEquals(100000, $response->viewData('totalEarned'));
        $this->assertSame(1, $response->viewData('wallets')->total());
    }
}
