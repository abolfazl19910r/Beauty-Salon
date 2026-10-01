<?php

namespace Tests\Feature\Idempotency;

use App\Models\Specialist;
use App\Models\User;
use App\Models\WalletSetting;
use App\Models\WithdrawalRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * دوبار ارسال یک فرم (دابل‌کلیک، رفرش، رتری شبکه) با همان idempotency_key (UUID مخفی در فرم) باید همان نتیجه‌ی اول را
 * بدهد، نه رکورد دوم یا خطا. کلید دیگر = درخواست جدید؛ همان کلید با داده‌ی دیگر = رد.
 */
class DoubleSubmitTest extends TestCase
{
    use RefreshDatabase;

    private function specialistUserWithBalance(int $balance): array
    {
        $user = User::factory()->create(['phone' => '09121234567']);
        $specialist = Specialist::factory()->create(['phone' => '09121234567', 'user_id' => $user->id]);
        $specialist->getOrCreateWallet()->update([
            'balance' => $balance, 'iban' => 'IR062960000000100324200001', 'account_holder_name' => 'متخصص',
        ]);
        WalletSetting::get()->update(['minimum_withdrawal_amount' => 10000, 'maximum_withdrawal_amount' => 50000000, 'withdrawal_fee_percentage' => 0]);

        return [$user, $specialist];
    }

    public function test_a_resubmitted_withdrawal_form_creates_one_request(): void
    {
        [$user, $specialist] = $this->specialistUserWithBalance(500000);
        $payload = ['amount' => 100000, 'method' => 'iban', 'idempotency_key' => (string) Str::uuid()];

        $first = $this->actingAs($user)->post(route('specialist.wallet.store-withdrawal'), $payload);
        $second = $this->actingAs($user->fresh())->post(route('specialist.wallet.store-withdrawal'), $payload);

        $this->assertSame(1, WithdrawalRequest::where('specialist_id', $specialist->id)->count());
        $this->assertSame(400000.0, (float) $specialist->wallet->fresh()->balance);
        $second->assertSessionHas('success');
        $this->assertSame($first->headers->get('Location'), $second->headers->get('Location'));
    }

    public function test_a_new_key_is_a_new_withdrawal_and_a_reused_key_with_another_amount_is_refused(): void
    {
        [$user, $specialist] = $this->specialistUserWithBalance(500000);
        $key = (string) Str::uuid();

        $this->actingAs($user)->post(route('specialist.wallet.store-withdrawal'), ['amount' => 100000, 'method' => 'iban', 'idempotency_key' => $key]);
        $this->actingAs($user->fresh())->post(route('specialist.wallet.store-withdrawal'), ['amount' => 50000, 'method' => 'iban', 'idempotency_key' => (string) Str::uuid()]);
        $this->actingAs($user->fresh())->post(route('specialist.wallet.store-withdrawal'), ['amount' => 70000, 'method' => 'iban', 'idempotency_key' => $key])
            ->assertSessionHas('error');

        $this->assertSame(2, WithdrawalRequest::where('specialist_id', $specialist->id)->count());
        $this->assertSame(350000.0, (float) $specialist->wallet->fresh()->balance);
    }

    public function test_the_withdrawal_form_carries_a_fresh_key(): void
    {
        [$user] = $this->specialistUserWithBalance(500000);

        $a = $this->actingAs($user)->get(route('specialist.wallet.create-withdrawal'))->assertOk()->getContent();
        $b = $this->actingAs($user)->get(route('specialist.wallet.create-withdrawal'))->assertOk()->getContent();

        preg_match('/name="idempotency_key" value="([0-9a-f-]{36})"/', $a, $ka);
        preg_match('/name="idempotency_key" value="([0-9a-f-]{36})"/', $b, $kb);
        $this->assertNotEmpty($ka);
        $this->assertNotSame($ka[1], $kb[1]);
    }
}
