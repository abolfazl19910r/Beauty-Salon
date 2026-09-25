<?php

namespace App\Console\Commands;

use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * دستور یک‌باره (تصمیم ۲۰۲۶-۰۹-۲۷) برای ردیف‌هایی که قبل از ممیزی جداسازی سالن‌ها با مالکیت اشتباه ساخته شده‌اند —
 * migrationها فقط schema هستند، پس اصلاح داده اینجاست:
 *  - user_notifications.user_id اعلان‌هایی که به مدل Specialist فرستاده شده بودند: شناسه‌ی متخصص به‌جای کاربرِ
 *    متخصص نوشته شده بود (به کاربر بی‌ربط، شاید در سالن دیگر، نسبت داده می‌شد). اصلاح با UserNotification::ownerUserId.
 *  - rewards بدون salon_id (قبل از ستون): به هیچ سالنی نشان داده نمی‌شوند؛ با --rewards-salon=<slug> به آن سالن
 *    داده می‌شوند، وگرنه فقط شمرده می‌شوند.
 * تکرار دستور بی‌اثر است. --dry-run فقط گزارش می‌دهد.
 */
class RepairLegacyTenantRows extends Command
{
    protected $signature = 'tenancy:repair-legacy-rows
        {--rewards-salon= : slug سالنی که جوایز بدون سالن به آن داده شوند}
        {--dry-run : فقط نمایش، بدون تغییر}';

    protected $description = 'اصلاح یک‌باره‌ی ردیف‌های قدیمی با مالکیت سالن/کاربر اشتباه (اعلان‌های متخصص، جوایز بدون سالن)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $slug = $this->option('rewards-salon');
        $salon = null;

        if ($slug !== null) {
            $salon = Salon::where('slug', $slug)->first();

            if (! $salon) {
                $this->error("سالنی با slug «{$slug}» پیدا نشد؛ هیچ تغییری داده نشد.");

                return self::FAILURE;
            }
        }

        $notifications = $this->repairSpecialistNotifications($dryRun);
        $this->info("اعلان‌های متخصص با user_id اشتباه: {$notifications}".($dryRun ? ' (dry-run)' : ' اصلاح شد'));

        $orphanRewards = DB::table('rewards')->whereNull('salon_id')->count();

        if ($salon && ! $dryRun) {
            DB::table('rewards')->whereNull('salon_id')->update(['salon_id' => $salon->id]);
            $this->info("جوایز بدون سالن: {$orphanRewards} به سالن «{$salon->slug}» داده شد");
        } else {
            $this->info("جوایز بدون سالن: {$orphanRewards}".($salon ? ' (dry-run)' : ' — برای سپردن به یک سالن --rewards-salon=<slug> بدهید'));
        }

        return self::SUCCESS;
    }

    private function repairSpecialistNotifications(bool $dryRun): int
    {
        $fixed = 0;
        $specialistType = (new Specialist)->getMorphClass();

        UserNotification::query()
            ->where('notifiable_type', $specialistType)
            ->select(['id', 'user_id', 'notifiable_type', 'notifiable_id'])
            ->chunkById(500, function ($rows) use (&$fixed, $dryRun) {
                foreach ($rows as $row) {
                    $owner = UserNotification::ownerUserId($row->notifiable_type, $row->notifiable_id);

                    if ($owner !== null && ! User::whereKey($owner)->exists()) {
                        $owner = null;
                    }

                    if ($row->user_id === $owner) {
                        continue;
                    }

                    $fixed++;

                    if (! $dryRun) {
                        DB::table('user_notifications')->where('id', $row->id)->update(['user_id' => $owner]);
                    }
                }
            });

        return $fixed;
    }
}
