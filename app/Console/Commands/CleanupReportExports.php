<?php

namespace App\Console\Commands;

use App\Repositories\Contracts\ReportExportRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupReportExports extends Command
{
    protected $signature = 'reports:cleanup-exports {--days=7 : تعداد روزهایی که یک خروجی ready/failed نگه داشته می‌شود}';

    protected $description = 'حذف فایل‌ها و رکوردهای قدیمی report_exports (ready/failed) تا از تجمیع فایل روی دیسک جلوگیری شود';

    public function handle(ReportExportRepositoryInterface $reportExportRepository): int
    {
        $days = (int) $this->option('days');

        $oldExports = $reportExportRepository->getOlderThanWithStatuses(['ready', 'failed'], now()->subDays($days));

        $deletedFiles = 0;

        foreach ($oldExports as $export) {
            if ($export->file_path && Storage::disk('local')->exists($export->file_path)) {
                Storage::disk('local')->delete($export->file_path);
                $deletedFiles++;
            }
        }

        $deletedRecords = $oldExports->count();

        $reportExportRepository->deleteByIds($oldExports->pluck('id'));

        $this->info("✅ {$deletedRecords} رکورد و {$deletedFiles} فایل قدیمی report_exports حذف شد.");

        return 0;
    }
}
