<?php

namespace App\Console\Commands;

use App\Services\Admin\Wallet\WalletAdminService;
use Illuminate\Console\Command;

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

    public function __construct(
        private readonly WalletAdminService $walletAdminService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * The actual settlement logic is in WalletAdminService::settlePendingIncomes() — the same method used by the admin panel's "Manual Settlement" button (so that this logic is only written and maintained once).
     */
    public function handle(): int
    {
        $this->info('🔄 شروع تسویه درآمدهای در انتظار...');

        $result = $this->walletAdminService->settlePendingIncomes(
            wallet: null,
            ignoreDelay: false,
            source: 'schedule'
        );

        $this->info('✅ تسویه به پایان رسید');
        $this->info("📊 تعداد تسویه شده: {$result['settledCount']}");
        $this->info('💰 مبلغ کل تسویه‌شده: '.number_format($result['settledAmount']));

        if ($result['failedCount'] > 0) {
            $this->warn("⚠️ تعداد ناموفق: {$result['failedCount']}");
        }

        return Command::SUCCESS;
    }
}
