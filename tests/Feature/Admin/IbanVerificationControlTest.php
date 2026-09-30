<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\SpecialistWallet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * تصمیم ۲۰۲۶-۰۹-۳۰: تأیید دستی شبا باید قابل ردگیری باشد، با تأیید صریح «نام را در بانک دیدم»،
 * قابل لغو باشد، و هیچ‌کس جز مالک سالن نتواند شبای خودش را تأیید کند.
 */
class IbanVerificationControlTest extends TestCase
{
    use RefreshDatabase;

    private const IBAN = 'IR820540102680020817909002';

    private Salon $salon;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->salon = Salon::where('slug', 'rasta')->firstOrFail();
        $this->owner = User::factory()->create(['is_admin' => true]);
    }

    private function financeStaff(): User
    {
        $staff = User::factory()->create(['is_admin' => false, 'user_type' => 'staff']);
        $this->salon->admins()->attach($staff->id, ['role' => 'staff']);
        $staff->roles()->sync([
            Role::where('name', 'staff')->firstOrFail()->id,
            Role::where('name', 'finance-access')->firstOrFail()->id,
        ]);

        return $staff;
    }

    private function walletOf(?User $user = null): SpecialistWallet
    {
        $specialist = Specialist::factory()->create($user ? ['user_id' => $user->id, 'phone' => $user->phone] : []);

        return SpecialistWallet::factory()->create([
            'specialist_id' => $specialist->id,
            'iban' => self::IBAN,
            'account_holder_name' => 'سارا محمدی',
            'iban_verified' => false,
        ]);
    }

    private function verify(User $actor, SpecialistWallet $wallet, bool $confirmed = true)
    {
        return $this->actingAs($actor)->post(
            "/admin/wallet/{$wallet->id}/verify-iban",
            $confirmed ? ['holder_name_checked' => '1'] : []
        );
    }

    public function test_verification_records_who_and_when(): void
    {
        $wallet = $this->walletOf();

        $this->verify($this->owner, $wallet)->assertSessionHas('success');

        $fresh = $wallet->fresh();
        $this->assertTrue($fresh->iban_verified);
        $this->assertSame($this->owner->id, $fresh->iban_verified_by);
        $this->assertNotNull($fresh->iban_verified_at);
    }

    public function test_verification_requires_confirming_the_holder_name_was_checked_at_the_bank(): void
    {
        $wallet = $this->walletOf();

        $this->verify($this->owner, $wallet, confirmed: false)->assertSessionHasErrors('holder_name_checked');

        $this->assertFalse((bool) $wallet->fresh()->iban_verified);
    }

    public function test_a_staff_member_cannot_verify_their_own_iban(): void
    {
        $staff = $this->financeStaff();
        $wallet = $this->walletOf($staff);

        $this->verify($staff, $wallet)->assertSessionHas('error');

        $this->assertFalse((bool) $wallet->fresh()->iban_verified);
    }

    public function test_a_staff_member_can_verify_another_specialists_iban(): void
    {
        $wallet = $this->walletOf();

        $this->verify($this->financeStaff(), $wallet)->assertSessionHas('success');

        $this->assertTrue((bool) $wallet->fresh()->iban_verified);
    }

    public function test_the_owner_may_verify_their_own_iban(): void
    {
        $wallet = $this->walletOf($this->owner);

        $this->verify($this->owner, $wallet)->assertSessionHas('success');

        $this->assertTrue((bool) $wallet->fresh()->iban_verified);
    }

    public function test_a_verification_can_be_revoked(): void
    {
        $wallet = $this->walletOf();
        $this->verify($this->owner, $wallet);

        $this->actingAs($this->owner)->post("/admin/wallet/{$wallet->id}/unverify-iban")->assertSessionHas('success');

        $fresh = $wallet->fresh();
        $this->assertFalse($fresh->iban_verified);
        $this->assertNull($fresh->iban_verified_by);
        $this->assertNull($fresh->iban_verified_at);
    }

    public function test_changing_the_iban_clears_the_verification_record(): void
    {
        $user = User::factory()->create(['phone' => '09121234567']);
        $wallet = $this->walletOf($user);
        $this->verify($this->owner, $wallet);

        $this->actingAs($user->fresh())->put(route('specialist.wallet.update-iban'), [
            'iban' => '062960000000100324200001',
            'account_holder_name' => 'سارا محمدی',
            'bank_name' => 'بانک ملت',
        ])->assertSessionHasNoErrors();

        $fresh = $wallet->fresh();
        $this->assertFalse($fresh->iban_verified);
        $this->assertNull($fresh->iban_verified_by);
        $this->assertNull($fresh->iban_verified_at);
    }

    public function test_the_wallet_page_shows_who_verified_and_the_bank_check_guidance(): void
    {
        $wallet = $this->walletOf();

        $this->actingAs($this->owner)->get("/admin/wallet/{$wallet->id}")
            ->assertOk()
            ->assertSee('holder_name_checked', false)
            ->assertSee('پایا');

        $this->verify($this->owner, $wallet);

        $this->actingAs($this->owner)->get("/admin/wallet/{$wallet->id}")
            ->assertSee($this->owner->name)
            ->assertSee(route('admin.wallet.unverify-iban', $wallet), false);
    }
}
