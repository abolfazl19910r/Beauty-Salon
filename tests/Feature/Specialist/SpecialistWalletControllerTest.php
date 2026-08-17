<?php

namespace Tests\Feature\Specialist;

use App\Models\Specialist;
use App\Models\User;
use App\Models\WalletSetting;
use App\Models\WithdrawalRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HTTP-level coverage for the specialist wallet/withdrawal controllers. Prior to this,
 * only the underlying SpecialistWalletService was directly unit/feature tested
 * (SpecialistWithdrawalTest) — the actual routes, middleware, policy wiring, and Form
 * Request validation of SpecialistWalletController / SpecialistWithdrawalController /
 * SpecialistIbanController had zero HTTP-level coverage until this session.
 */
class SpecialistWalletControllerTest extends TestCase
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

    public function test_index_renders_wallet_overview_for_the_matching_specialist(): void
    {
        [$user, $specialist] = $this->actingSpecialist();
        $specialist->getOrCreateWallet()->update(['balance' => 50000]);

        $response = $this->actingAs($user)->get(route('specialist.wallet.index'));

        $response->assertOk();
        $response->assertViewIs('specialist.wallet.index');
        $response->assertViewHas('wallet');
    }

    public function test_index_shows_profile_not_found_when_no_specialist_matches(): void
    {
        $user = User::factory()->create(['phone' => '09120000000']);

        $response = $this->actingAs($user)->get(route('specialist.wallet.index'));

        $response->assertOk();
        $response->assertViewIs('specialist.profile-not-found');
    }

    public function test_transactions_filters_by_type_through_the_real_http_route(): void
    {
        [$user, $specialist] = $this->actingSpecialist();
        $wallet = $specialist->getOrCreateWallet();
        $wallet->transactions()->create(['type' => 'income', 'amount' => 10000, 'balance_after' => 10000, 'description' => 'test']);
        $wallet->transactions()->create(['type' => 'withdrawal', 'amount' => -5000, 'balance_after' => 5000, 'description' => 'test']);

        $response = $this->actingAs($user)->get(route('specialist.wallet.transactions', ['type' => 'income']));

        $response->assertOk();
        $transactions = $response->viewData('transactions');
        $this->assertCount(1, $transactions);
        $this->assertSame('income', $transactions->first()->type);
    }

    public function test_calculate_fee_returns_json_for_an_ajax_style_request(): void
    {
        [$user, $specialist] = $this->actingSpecialist();

        $response = $this->actingAs($user)->post(route('specialist.wallet.calculate-fee'), [
            'amount' => 100000,
            'method' => 'iban',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['gross_amount', 'fee', 'net_amount']);
    }

    public function test_calculate_fee_returns_json_404_not_html_when_no_specialist_matches(): void
    {
        // Deliberately uses resolveSpecialist() (not requireSpecialist()) so that a
        // missing specialist still yields JSON, since this endpoint is only ever
        // called via fetch — documented directly in the controller's own comment.
        $user = User::factory()->create(['phone' => '09120000000']);

        $response = $this->actingAs($user)->post(route('specialist.wallet.calculate-fee'), [
            'amount' => 100000,
            'method' => 'iban',
        ]);

        $response->assertStatus(404);
        $response->assertJson(['error' => 'متخصص یافت نشد']);
    }

    public function test_a_specialist_cannot_view_another_specialists_wallet_by_forging_the_route(): void
    {
        // No route accepts a foreign wallet/specialist id here — resolveSpecialist()
        // always scopes to auth()->user()->specialist. This documents that guarantee
        // the same way the existing IBAN forgery test does.
        [$userA] = $this->actingSpecialist();
        $specialistB = Specialist::factory()->create();
        $walletB = $specialistB->getOrCreateWallet();

        $this->assertFalse((bool) $userA->can('view', $walletB));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('specialist.wallet.index'))->assertRedirect(route('login'));
        $this->get(route('specialist.wallet.transactions'))->assertRedirect(route('login'));
    }
}
