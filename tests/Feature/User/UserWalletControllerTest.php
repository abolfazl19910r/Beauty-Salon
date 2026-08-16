<?php

namespace Tests\Feature\User;

use App\Models\User;
use App\Models\UserWalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UserWalletControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_shows_current_month_spent_and_refunded_totals(): void
    {
        $user = User::factory()->create();
        $wallet = $user->getOrCreateWallet();

        UserWalletTransaction::factory()->create([
            'wallet_id' => $wallet->id, 'type' => 'payment', 'amount' => 50000,
        ]);
        UserWalletTransaction::factory()->create([
            'wallet_id' => $wallet->id, 'type' => 'refund', 'amount' => 20000,
        ]);
        // A transaction from last month must not be counted in "this month" totals.
        UserWalletTransaction::factory()->create([
            'wallet_id' => $wallet->id, 'type' => 'payment', 'amount' => 999999,
            'created_at' => now()->subMonths(2),
        ]);

        $response = $this->actingAs($user)->get(route('wallet.index'));

        $response->assertOk();
        $response->assertViewHas('currentMonthSpent', '50000.00');
        $response->assertViewHas('currentMonthRefunds', '20000.00');
    }

    public function test_transactions_filters_by_type(): void
    {
        $user = User::factory()->create();
        $wallet = $user->getOrCreateWallet();
        UserWalletTransaction::factory()->create(['wallet_id' => $wallet->id, 'type' => 'deposit']);
        UserWalletTransaction::factory()->create(['wallet_id' => $wallet->id, 'type' => 'payment']);

        $response = $this->actingAs($user)->get(route('wallet.transactions', ['type' => 'deposit']));

        $transactions = $response->viewData('transactions');
        $this->assertCount(1, $transactions);
        $this->assertSame('deposit', $transactions->first()->type);
    }

    public function test_transactions_filters_by_jalali_date_range(): void
    {
        $user = User::factory()->create();
        $wallet = $user->getOrCreateWallet();
        $inRange = UserWalletTransaction::factory()->create([
            'wallet_id' => $wallet->id, 'created_at' => now()->setDate(2026, 4, 15),
        ]);
        UserWalletTransaction::factory()->create([
            'wallet_id' => $wallet->id, 'created_at' => now()->setDate(2026, 6, 15),
        ]);

        $response = $this->actingAs($user)->get(route('wallet.transactions', [
            'date_from' => '1405/01/01',
            'date_to' => '1405/02/28',
        ]));

        $transactions = $response->viewData('transactions');
        $this->assertCount(1, $transactions);
        $this->assertSame($inRange->id, $transactions->first()->id);
    }

    public function test_show_transaction_is_forbidden_for_a_transaction_belonging_to_another_users_wallet(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherWallet = $otherUser->getOrCreateWallet();
        $transaction = UserWalletTransaction::factory()->create(['wallet_id' => $otherWallet->id]);

        $this->actingAs($user)
            ->get(route('wallet.transactions.show', $transaction))
            ->assertForbidden();
    }

    public function test_show_transaction_succeeds_for_the_owning_user(): void
    {
        $user = User::factory()->create();
        $wallet = $user->getOrCreateWallet();
        $transaction = UserWalletTransaction::factory()->create(['wallet_id' => $wallet->id]);

        $this->actingAs($user)
            ->get(route('wallet.transactions.show', $transaction))
            ->assertOk();
    }

    public function test_process_charge_rejects_an_amount_below_the_minimum(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('wallet.charge.process'), ['amount' => '5000']);

        $response->assertSessionHasErrors('amount');
    }

    public function test_process_charge_accepts_persian_digit_formatted_amounts(): void
    {
        $user = User::factory()->create();
        Http::fake([
            '*' => Http::response(['data' => ['authority' => 'A123', 'code' => 100], 'errors' => []], 200),
        ]);

        $response = $this->actingAs($user)->post(route('wallet.charge.process'), [
            // Persian digits for "100000" with thousands separators
            'amount' => '۱۰۰,۰۰۰',
        ]);

        $response->assertSessionHas('wallet_charge_pending');
        $this->assertSame(100000.0, session('wallet_charge_pending')['amount']);
    }

    public function test_charge_callback_credits_the_wallet_on_a_successful_gateway_verification(): void
    {
        $user = User::factory()->create();
        $wallet = $user->getOrCreateWallet();

        session([
            'wallet_charge_pending' => [
                'user_id' => $user->id,
                'amount' => 100000,
                'created_at' => now()->timestamp,
            ],
        ]);

        Http::fake([
            '*' => Http::response(['data' => ['code' => 100, 'ref_id' => 'REF123']], 200),
        ]);

        $response = $this->actingAs($user)->get(route('wallet.charge.callback', [
            'Authority' => 'A123', 'Status' => 'OK',
        ]));

        $response->assertRedirect(route('wallet.charge.success'));
        $this->assertEquals(100000, $wallet->fresh()->balance);
        $this->assertDatabaseHas('user_wallet_transactions', [
            'wallet_id' => $wallet->id, 'type' => 'deposit', 'amount' => 100000,
        ]);
        $this->assertNull(session('wallet_charge_pending'));
    }

    public function test_charge_callback_does_not_credit_the_wallet_on_a_failed_gateway_verification(): void
    {
        $user = User::factory()->create();
        $wallet = $user->getOrCreateWallet();

        session([
            'wallet_charge_pending' => [
                'user_id' => $user->id,
                'amount' => 100000,
                'created_at' => now()->timestamp,
            ],
        ]);

        Http::fake([
            '*' => Http::response(['data' => ['code' => 101], 'errors' => ['message' => 'rejected']], 200),
        ]);

        $response = $this->actingAs($user)->get(route('wallet.charge.callback', [
            'Authority' => 'A123', 'Status' => 'NOK',
        ]));

        $response->assertRedirect(route('wallet.index'));
        $this->assertEquals(0, $wallet->fresh()->balance);
    }

    public function test_charge_success_redirects_away_without_a_pending_success_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('wallet.charge.success'))->assertRedirect(route('wallet.index'));
    }
}
