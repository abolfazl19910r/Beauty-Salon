<?php

namespace Tests\Feature\Specialist;

use App\Models\Specialist;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * کیف پول کارشناس (بسته‌ی ۳ ظاهری، ۲۰۲۶-۱۱-۲۵): علامت مبلغ تراکنش در راست‌به‌چپ سر جایش (پیش‌تر «159,300+ تومان»)،
 * «درآمد/برداشت این ماه» با ماه شمسی، و «تأیید» با همزه در متن‌های پنل.
 */
class SpecialistWalletRefreshTest extends TestCase
{
    use RefreshDatabase;

    private Specialist $specialist;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-25 14:00:00')); // ۴ آذر ۱۴۰۵
        $this->specialist = Specialist::factory()->create();
        $wallet = $this->specialist->getOrCreateWallet();

        $tx = fn (string $type, int $amount, string $at) => WalletTransaction::query()->forceCreate([
            'wallet_id' => $wallet->id, 'type' => $type, 'amount' => $amount, 'balance_after' => 0,
            'description' => $type, 'created_at' => $at, 'updated_at' => $at,
        ]);
        $tx('income', 159300, '2026-11-24 10:00:00');
        $tx('income', 700000, '2026-11-20 10:00:00');       // ۲۹ آبان — ماه قبل
        $tx('withdrawal', -200000, '2026-11-23 10:00:00');
        $tx('withdrawal', -50000, '2026-11-02 10:00:00');    // ۱۱ آبان — ماه قبل
    }

    public function test_amounts_carry_an_isolated_sign_in_persian_digits(): void
    {
        $user = User::find($this->specialist->user_id);

        foreach ([route('specialist.wallet.index'), route('specialist.wallet.transactions')] as $url) {
            $html = $this->actingAs($user)->get($url)->assertOk()->getContent();

            $this->assertStringContainsString("\u{2066}+۱۵۹٬۳۰۰\u{2069} تومان", $html);
            $this->assertStringContainsString("\u{2066}−۲۰۰٬۰۰۰\u{2069} تومان", $html);
            $this->assertStringNotContainsString('159,300+', $html);
            $this->assertStringNotContainsString('+159,300', $html);
        }
    }

    public function test_this_month_is_the_jalali_month(): void
    {
        $response = $this->actingAs(User::find($this->specialist->user_id))->get(route('specialist.wallet.index'))->assertOk();

        $this->assertSame(159300.0, $response->viewData('currentMonthIncome'));
        $this->assertSame(-200000.0, $response->viewData('currentMonthWithdrawals'));
        $response->assertSee('درآمد آذر')->assertSee('برداشت آذر');
    }

    public function test_panel_texts_use_the_hamza_spelling(): void
    {
        $user = User::find($this->specialist->user_id);

        foreach (['specialist.wallet.index', 'specialist.schedule', 'specialist.leaves.index', 'specialist.leaves.create', 'specialist.wallet.edit-iban'] as $route) {
            $this->assertStringNotContainsString('تایید', $this->actingAs($user)->get(route($route))->assertOk()->getContent(), $route);
        }
    }
}
