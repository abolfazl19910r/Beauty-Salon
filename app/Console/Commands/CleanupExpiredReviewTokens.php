<?php

namespace App\Console\Commands;

use App\Models\ReviewToken;
use Illuminate\Console\Command;

class CleanupExpiredReviewTokens extends Command
{
    protected $signature = 'review-tokens:cleanup';

    protected $description = 'حذف توکن‌های منقضی شده نظرسنجی';

    public function handle()
    {
        $deletedCount = ReviewToken::expired()->delete();

        $this->info("✅ {$deletedCount} توکن منقضی حذف شد.");

        return 0;
    }
}
