<?php

namespace Tests\Feature\Wallet;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Specialist;
use App\Services\Admin\Wallet\WalletAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * wallet:settle-pending همه‌ی درآمدهای pending همه‌ی سالن‌ها را یک‌جا در حافظه می‌آورد (۱۰۰۰ سالن: ۹۷ هزار ردیف، اوج ۴۵۰ MB —
 * بیشتر از memory_limit رایج CLI). باید تکه‌تکه (lazyById) خوانده شود، و تسویه‌ی ردیف‌های یک تکه نباید ردیفی از تکه‌ی بعد را جا بیندازد.
 */
class SettlePendingIncomesChunkingTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_incomes_are_read_in_chunks_and_every_due_one_settles(): void
    {
        $service = BeautyService::factory()->create(['price' => 200000]);
        $specialist = Specialist::factory()->create();
        $wallet = $specialist->getOrCreateWallet();
        foreach (range(8, 19) as $hour) {
            $booking = Booking::factory()->create([
                'service_id' => $service->id, 'specialist_id' => $specialist->id,
                'booking_time' => now()->subDays(3)->setTime($hour, 0),
            ]);
            $tx = $wallet->addIncome(10000, $booking->id);
            $tx->update(['metadata' => ['settlement_date' => now()->subDay()->toDateString(), 'status' => 'pending']]);
        }

        $pages = 0;
        DB::listen(function ($q) use (&$pages) {
            if (str_starts_with($q->sql, 'select * from "wallet_transactions"') || str_starts_with($q->sql, 'select * from `wallet_transactions`')) {
                $pages++;
            }
        });

        $settle = app(WalletAdminService::class);
        $settle->settlementChunkSize = 5;
        $result = $settle->settlePendingIncomes();

        $this->assertSame(12, $result['settledCount']);
        $this->assertSame(3, $pages, 'pending incomes should be read in pages of 5 (12 rows -> 3 pages)');
        $this->assertSame(120000.0, (float) $wallet->fresh()->balance);
        $this->assertSame(0.0, (float) $wallet->fresh()->pending_amount);
    }
}
