<?php

namespace Tests\Feature\Wallet;

use App\Models\Specialist;
use App\Models\User;
use App\Models\WalletSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * شبایی که پیش از چک رقم کنترلی ذخیره شده (یا مستقیم در دیتابیس نوشته شده) نباید به درخواست برداشت برسد.
 */
class WithdrawalStoredIbanTest extends TestCase
{
    use RefreshDatabase;

    private const TYPO = 'IR820540102680020871909002';

    private const VALID = 'IR820540102680020817909002';

    private function specialistWithIban(string $iban): array
    {
        $user = User::factory()->create(['phone' => '09121234567']);
        $specialist = Specialist::factory()->create(['phone' => '09121234567', 'user_id' => $user->id]);
        $specialist->getOrCreateWallet()->update([
            'iban' => $iban,
            'account_holder_name' => 'کاربر تست',
            'balance' => 500000,
        ]);
        WalletSetting::get()->update(['minimum_withdrawal_amount' => 5000, 'maximum_withdrawal_amount' => 1000000]);

        return [$user, $specialist];
    }

    public function test_a_withdrawal_is_refused_when_the_stored_iban_fails_the_checksum(): void
    {
        [$user, $specialist] = $this->specialistWithIban(self::TYPO);

        $response = $this->actingAs($user)->post(route('specialist.wallet.store-withdrawal'), [
            'amount' => 100000,
            'method' => 'iban',
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('شبا', session('error'));
        $this->assertDatabaseCount('withdrawal_requests', 0);
        $this->assertEquals(500000, (float) $specialist->getOrCreateWallet()->fresh()->balance);
    }

    public function test_the_withdrawal_form_sends_the_specialist_to_fix_an_invalid_stored_iban(): void
    {
        [$user] = $this->specialistWithIban(self::TYPO);

        $this->actingAs($user)->get(route('specialist.wallet.create-withdrawal'))
            ->assertRedirect(route('specialist.wallet.edit-iban'))
            ->assertSessionHas('error');
    }

    public function test_a_valid_stored_iban_still_withdraws(): void
    {
        [$user] = $this->specialistWithIban(self::VALID);

        $this->actingAs($user)->post(route('specialist.wallet.store-withdrawal'), [
            'amount' => 100000,
            'method' => 'iban',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('withdrawal_requests', ['iban' => self::VALID, 'status' => 'pending']);
    }
}
