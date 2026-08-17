<?php

namespace Tests\Feature\Specialist;

use App\Models\Specialist;
use App\Models\User;
use App\Models\WalletSetting;
use App\Models\WithdrawalRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpecialistWithdrawalControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actingSpecialist(): array
    {
        $user = User::factory()->create(['phone' => '09121234567']);
        $specialist = Specialist::factory()->create([
            'phone' => '09121234567',
            'user_id' => $user->id,
        ]);

        return [$user, $specialist];
    }

    private function withIban(Specialist $specialist): void
    {
        $specialist->getOrCreateWallet()->update([
            'iban' => 'IR820540102680020817909002',
            'account_holder_name' => 'کاربر تست',
        ]);
    }

    public function test_create_page_redirects_to_iban_form_when_no_iban_is_on_file(): void
    {
        [$user, $specialist] = $this->actingSpecialist();

        $response = $this->actingAs($user)->get(route('specialist.wallet.create-withdrawal'));

        $response->assertRedirect(route('specialist.wallet.edit-iban'));
    }

    public function test_create_page_renders_once_an_iban_is_on_file(): void
    {
        [$user, $specialist] = $this->actingSpecialist();
        $this->withIban($specialist);

        $response = $this->actingAs($user)->get(route('specialist.wallet.create-withdrawal'));

        $response->assertOk();
        $response->assertViewIs('specialist.wallet.create-withdrawal');
    }

    public function test_create_page_shows_profile_not_found_when_no_specialist_matches(): void
    {
        $user = User::factory()->create(['phone' => '09120000000']);

        $response = $this->actingAs($user)->get(route('specialist.wallet.create-withdrawal'));

        $response->assertOk();
        $response->assertViewIs('specialist.profile-not-found');
    }

    public function test_store_creates_a_withdrawal_and_redirects_with_the_reference_code(): void
    {
        [$user, $specialist] = $this->actingSpecialist();
        $this->withIban($specialist);
        $specialist->getOrCreateWallet()->update(['balance' => 500000]);
        WalletSetting::first()->update(['minimum_withdrawal_amount' => 50000, 'maximum_withdrawal_amount' => 1000000]);

        $response = $this->actingAs($user)->post(route('specialist.wallet.store-withdrawal'), [
            'amount' => 100000,
            'method' => 'iban',
        ]);

        $response->assertRedirect(route('specialist.wallet.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('withdrawal_requests', [
            'wallet_id' => $specialist->getOrCreateWallet()->id,
            'status' => 'pending',
        ]);
    }

    public function test_store_rejects_an_amount_below_the_minimum_via_the_form_request(): void
    {
        [$user, $specialist] = $this->actingSpecialist();
        $this->withIban($specialist);
        $specialist->getOrCreateWallet()->update(['balance' => 500000]);
        WalletSetting::first()->update(['minimum_withdrawal_amount' => 100000]);

        $response = $this->actingAs($user)->post(route('specialist.wallet.store-withdrawal'), [
            'amount' => 50000,
            'method' => 'iban',
        ]);

        $response->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('withdrawal_requests', 0);
    }

    public function test_store_shows_the_services_failure_message_when_balance_is_insufficient(): void
    {
        [$user, $specialist] = $this->actingSpecialist();
        $this->withIban($specialist);
        $specialist->getOrCreateWallet()->update(['balance' => 5000]);
        WalletSetting::first()->update(['minimum_withdrawal_amount' => 5000, 'maximum_withdrawal_amount' => 1000000]);

        $response = $this->actingAs($user)->post(route('specialist.wallet.store-withdrawal'), [
            'amount' => 8000,
            'method' => 'iban',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('withdrawal_requests', 0);
    }

    public function test_cancel_refunds_the_wallet_and_marks_the_request_cancelled(): void
    {
        [$user, $specialist] = $this->actingSpecialist();
        $wallet = $specialist->getOrCreateWallet();
        $wallet->update(['balance' => 400000]);
        $withdrawal = WithdrawalRequest::factory()->create([
            'wallet_id' => $wallet->id,
            'specialist_id' => $specialist->id,
            'amount' => 100000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->delete(route('specialist.wallet.cancel-withdrawal', $withdrawal));

        $response->assertRedirect(route('specialist.wallet.index'));
        $response->assertSessionHas('success');
        $this->assertSame('cancelled', $withdrawal->fresh()->status);
        $this->assertSame(500000.0, (float) $wallet->fresh()->balance);
    }

    public function test_cancel_refuses_a_withdrawal_that_is_already_completed(): void
    {
        [$user, $specialist] = $this->actingSpecialist();
        $wallet = $specialist->getOrCreateWallet();
        $withdrawal = WithdrawalRequest::factory()->create([
            'wallet_id' => $wallet->id,
            'specialist_id' => $specialist->id,
            'amount' => 100000,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($user)->delete(route('specialist.wallet.cancel-withdrawal', $withdrawal));

        $response->assertSessionHas('error');
        $this->assertSame('completed', $withdrawal->fresh()->status);
    }

    public function test_a_specialist_cannot_cancel_another_specialists_withdrawal(): void
    {
        [$user] = $this->actingSpecialist();
        $otherSpecialist = Specialist::factory()->create();
        $otherWallet = $otherSpecialist->getOrCreateWallet();
        $withdrawal = WithdrawalRequest::factory()->create([
            'wallet_id' => $otherWallet->id,
            'specialist_id' => $otherSpecialist->id,
            'amount' => 100000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->delete(route('specialist.wallet.cancel-withdrawal', $withdrawal));

        $response->assertForbidden();
        $this->assertSame('pending', $withdrawal->fresh()->status);
    }

    public function test_guest_is_redirected_to_login_on_all_withdrawal_routes(): void
    {
        $withdrawal = WithdrawalRequest::factory()->create();

        $this->get(route('specialist.wallet.create-withdrawal'))->assertRedirect(route('login'));
        $this->post(route('specialist.wallet.store-withdrawal'), [])->assertRedirect(route('login'));
        $this->delete(route('specialist.wallet.cancel-withdrawal', $withdrawal))->assertRedirect(route('login'));
    }
}
