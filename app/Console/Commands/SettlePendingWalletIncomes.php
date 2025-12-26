<?php

namespace App\Console\Commands;

use App\Models\WalletTransaction;
use App\Models\WalletSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SettlePendingWalletIncomes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallet:settle-pending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'انتقال درآمدهای pending به balance بعد از گذشت مدت تسویه';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 شروع تسویه درآمدهای در انتظار...');

        $settings = WalletSetting::first();
        $settlementDelay = $settings->settlement_delay_days ?? 2;

        $pendingTransactions = WalletTransaction::where('type', 'income')
            ->whereJsonContains('metadata->status', 'pending')
            ->get();

        $settledCount = 0;
        $failedCount = 0;

        foreach ($pendingTransactions as $transaction) {
            try {
                $settlementDate = $transaction->metadata['settlement_date'] ?? null;

                if (!$settlementDate) {
                    continue;
                }

                if (Carbon::parse($settlementDate)->isPast()) {
                    DB::beginTransaction();

                    $wallet = $transaction->wallet;
                    $amount = $transaction->amount;

                    $wallet->settlePendingAmount($amount);

                    $metadata = $transaction->metadata;
                    $metadata['status'] = 'settled';
                    $metadata['settled_at'] = now()->toDateTimeString();
                    $transaction->update(['metadata' => $metadata]);

                    $transaction->update(['balance_after' => $wallet->balance]);

                    DB::commit();

                    $settledCount++;
                    $this->info("✅ تراکنش #{$transaction->id} تسویه شد - مبلغ: " . number_format($amount));
                }
            } catch (\Exception $e) {
                DB::rollBack();
                $failedCount++;

                Log::error('❌ خطا در تسویه تراکنش', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage()
                ]);

                $this->error("❌ خطا در تسویه تراکنش #{$transaction->id}: {$e->getMessage()}");
            }
        }

        $this->info("✅ تسویه به پایان رسید");
        $this->info("📊 تعداد تسویه شده: {$settledCount}");

        if ($failedCount > 0) {
            $this->warn("⚠️ تعداد ناموفق: {$failedCount}");
        }

        return Command::SUCCESS;
    }
}
