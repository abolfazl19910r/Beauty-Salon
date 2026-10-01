<?php

namespace Tests\Feature\Specialist;

use App\Models\Specialist;
use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * برداشتی که تسویه‌ی خودکارش «نتیجه‌ی نامعلوم» گرفته (needs_manual_check، وضعیت processing) شاید واقعاً واریز شده باشد؛
 * فقط مدیر بعد از دیدن پنل درگاه تأیید یا رد می‌کند. لغو توسط متخصص پول را به کیف پول برمی‌گرداند و احتمال پرداخت دوباره است.
 * برداشت processing عادی (در صف، هنوز ارسال‌نشده) همچنان قابل لغو است — job بعد از لغو کاری نمی‌کند.
 */
class WithdrawalCancelUnknownPayoutTest extends TestCase
{
    use RefreshDatabase;

    private function withdrawal(array $attributes): array
    {
        $user = User::factory()->create(['phone' => '09121234567']);
        $specialist = Specialist::factory()->create(['phone' => '09121234567', 'user_id' => $user->id]);
        $wallet = $specialist->getOrCreateWallet();
        $wallet->update(['balance' => 400000]);
        $withdrawal = WithdrawalRequest::factory()->create(array_merge([
            'wallet_id' => $wallet->id, 'specialist_id' => $specialist->id, 'amount' => 100000,
        ], $attributes));

        return [$user, $wallet, $withdrawal];
    }

    public function test_a_withdrawal_with_an_unknown_payout_result_cannot_be_cancelled_by_the_specialist(): void
    {
        [$user, $wallet, $withdrawal] = $this->withdrawal(['status' => 'processing', 'needs_manual_check' => true]);

        $this->actingAs($user)->delete(route('specialist.wallet.cancel-withdrawal', $withdrawal))->assertSessionHas('error');

        $this->assertSame('processing', $withdrawal->fresh()->status);
        $this->assertSame(400000.0, (float) $wallet->fresh()->balance);
        $this->assertFalse($withdrawal->fresh()->canBeCancelled());
    }

    public function test_a_queued_processing_withdrawal_can_still_be_cancelled(): void
    {
        [$user, $wallet, $withdrawal] = $this->withdrawal(['status' => 'processing', 'needs_manual_check' => false]);

        $this->actingAs($user)->delete(route('specialist.wallet.cancel-withdrawal', $withdrawal))->assertSessionHas('success');

        $this->assertSame('cancelled', $withdrawal->fresh()->status);
        $this->assertSame(500000.0, (float) $wallet->fresh()->balance);
    }
}
