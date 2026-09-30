<?php

namespace Tests\Feature\Admin;

use App\Models\SpecialistWallet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * «تأیید شبا» توسط مدیر فقط برای شبای ثبت‌شده و خوش‌ساخت معنی دارد.
 */
class AdminVerifyIbanTest extends TestCase
{
    use RefreshDatabase;

    private function verify(SpecialistWallet $wallet)
    {
        $admin = User::factory()->create(['is_admin' => true]);

        return $this->actingAs($admin)->post("/admin/wallet/{$wallet->id}/verify-iban");
    }

    public function test_a_wallet_without_an_iban_cannot_be_verified(): void
    {
        $wallet = SpecialistWallet::factory()->create(['iban' => null, 'iban_verified' => false]);

        $this->verify($wallet)->assertRedirect()->assertSessionHas('error');

        $this->assertFalse((bool) $wallet->fresh()->iban_verified);
    }

    public function test_an_iban_that_fails_the_checksum_cannot_be_verified(): void
    {
        $wallet = SpecialistWallet::factory()->create(['iban' => 'IR820540102680020871909002', 'iban_verified' => false]);

        $this->verify($wallet)->assertRedirect()->assertSessionHas('error');

        $this->assertFalse((bool) $wallet->fresh()->iban_verified);
    }

    public function test_a_valid_iban_is_verified(): void
    {
        $wallet = SpecialistWallet::factory()->create(['iban' => 'IR820540102680020817909002', 'iban_verified' => false]);

        $this->verify($wallet)->assertRedirect()->assertSessionHas('success');

        $this->assertTrue((bool) $wallet->fresh()->iban_verified);
    }

    public function test_the_wallet_page_flags_an_invalid_iban_instead_of_offering_to_verify_it(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $wallet = SpecialistWallet::factory()->create(['iban' => 'IR820540102680020871909002']);

        $this->actingAs($admin)->get("/admin/wallet/{$wallet->id}")
            ->assertOk()
            ->assertSee('شبا نامعتبر است')
            ->assertDontSee(route('admin.wallet.verify-iban', $wallet));
    }
}
