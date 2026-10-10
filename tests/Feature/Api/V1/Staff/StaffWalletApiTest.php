<?php

namespace Tests\Feature\Api\V1\Staff;

use App\Models\Specialist;
use App\Models\User;
use App\Models\WalletSetting;
use App\Models\WithdrawalRequest;
use App\Notifications\Admin\Wallet\SpecialistIbanChangedNotification;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * کیف پول اپ همکار (بسته‌ی ۲): موجودی، برداشت (idempotent، لغو)، تغییر شبا با رمز فعلی و اعلان به مالک.
 */
class StaffWalletApiTest extends TestCase
{
    use RefreshDatabase;

    protected const IBAN = '820540102680020817909002';

    protected Specialist $specialist;

    protected User $user;

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        WalletSetting::get()->update(['minimum_withdrawal_amount' => 10000, 'maximum_withdrawal_amount' => 50000000]);
        $this->specialist = Specialist::factory()->create();
        $this->user = User::find($this->specialist->user_id);
        $this->user->forceFill(['password' => Hash::make('staff-pass-1')])->save();
        $this->token = $this->user->createToken('A17', ['staff'], now()->addDays(90))->plainTextToken;

        $wallet = $this->specialist->getOrCreateWallet();
        $wallet->forceFill(['balance' => 500000, 'iban' => 'IR'.self::IBAN, 'account_holder_name' => 'متخصص', 'bank_name' => 'ملی', 'iban_verified' => true])->save();
    }

    protected function api(): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($this->token);
    }

    public function test_wallet_overview_masks_the_iban(): void
    {
        $response = $this->api()->getJson('/api/v1/staff/wallet');

        $response->assertOk()
            ->assertJsonPath('data.balance', 500000)
            ->assertJsonPath('data.iban.masked', 'IR82…9002')
            ->assertJsonPath('data.iban.verified', true)
            ->assertJsonPath('data.withdrawal_limits.minimum', 10000);
        $this->assertStringNotContainsString(self::IBAN, $response->getContent());
    }

    public function test_withdrawal_is_idempotent_and_can_be_cancelled_once(): void
    {
        $key = (string) Str::uuid();
        $body = ['amount' => 200000, 'method' => 'iban', 'idempotency_key' => $key];

        $first = $this->api()->postJson('/api/v1/staff/wallet/withdrawals', $body);
        $first->assertCreated()->assertJsonPath('meta.replayed', false)->assertJsonPath('data.status', 'pending');
        $this->api()->postJson('/api/v1/staff/wallet/withdrawals', $body)
            ->assertOk()
            ->assertJsonPath('meta.replayed', true)
            ->assertJsonPath('data.id', $first->json('data.id'));
        $this->assertEquals(300000, (float) $this->specialist->getOrCreateWallet()->fresh()->balance);

        $this->api()->postJson('/api/v1/staff/wallet/withdrawals', ['amount' => 900000, 'method' => 'iban', 'idempotency_key' => (string) Str::uuid()])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'withdrawal_rejected');

        $this->api()->getJson('/api/v1/staff/wallet/withdrawals')->assertOk()->assertJsonPath('meta.total', 1);

        $id = $first->json('data.id');
        $this->api()->deleteJson('/api/v1/staff/wallet/withdrawals/'.$id)->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->assertEquals(500000, (float) $this->specialist->getOrCreateWallet()->fresh()->balance);
        $this->api()->deleteJson('/api/v1/staff/wallet/withdrawals/'.$id)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'withdrawal_not_cancellable');
    }

    public function test_another_specialists_withdrawal_is_404(): void
    {
        $other = Specialist::factory()->create();
        $otherWallet = $other->getOrCreateWallet();
        $foreign = WithdrawalRequest::create([
            'wallet_id' => $otherWallet->id, 'specialist_id' => $other->id, 'amount' => 1000, 'fee' => 0,
            'net_amount' => 1000, 'method' => 'iban', 'status' => 'pending', 'iban' => 'IR'.self::IBAN, 'account_holder_name' => 'دیگری',
        ]);

        $this->api()->deleteJson('/api/v1/staff/wallet/withdrawals/'.$foreign->id)->assertNotFound();
        $this->assertSame('pending', $foreign->fresh()->status);
    }

    public function test_iban_change_needs_the_current_password_and_notifies_the_owner(): void
    {
        Notification::fake();
        $salon = app(CurrentSalon::class)->get();
        $admin = User::factory()->create(['user_type' => 'staff', 'salon_id' => null, 'is_admin' => true]);
        $salon->admins()->syncWithoutDetaching([$admin->id => ['role' => 'owner']]);
        $owner = $salon->owner();
        $this->assertNotNull($owner);
        $newIban = ['iban' => self::IBAN, 'account_holder_name' => 'نام تازه', 'bank_name' => 'ملت'];

        $this->api()->putJson('/api/v1/staff/wallet/iban', $newIban + ['current_password' => 'wrong-pass'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonStructure(['error' => ['fields' => ['current_password']]]);
        $this->assertSame('متخصص', $this->specialist->getOrCreateWallet()->fresh()->account_holder_name);
        Notification::assertNothingSent();

        $this->api()->putJson('/api/v1/staff/wallet/iban', $newIban + ['current_password' => 'staff-pass-1'])
            ->assertOk()
            ->assertJsonPath('data.iban.verified', false)
            ->assertJsonPath('data.iban.account_holder_name', 'نام تازه');

        $this->assertFalse((bool) $this->specialist->getOrCreateWallet()->fresh()->iban_verified);
        Notification::assertSentTo($owner, SpecialistIbanChangedNotification::class);
    }

    public function test_iban_endpoint_is_strictly_rate_limited(): void
    {
        foreach (range(1, 5) as $i) {
            $this->api()->putJson('/api/v1/staff/wallet/iban', ['current_password' => 'guess-'.$i])->assertStatus(422);
        }

        $this->api()->putJson('/api/v1/staff/wallet/iban', ['current_password' => 'guess-6'])
            ->assertStatus(429)
            ->assertJsonPath('error.code', 'too_many_attempts');
    }

    public function test_invalid_iban_checksum_is_rejected_like_the_web(): void
    {
        $this->api()->putJson('/api/v1/staff/wallet/iban', [
            'iban' => '820540102680020817909003', 'account_holder_name' => 'نام', 'bank_name' => 'ملت', 'current_password' => 'staff-pass-1',
        ])->assertStatus(422)->assertJsonStructure(['error' => ['fields' => ['iban']]]);
    }
}
