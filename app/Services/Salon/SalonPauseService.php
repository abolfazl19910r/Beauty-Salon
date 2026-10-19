<?php

namespace App\Services\Salon;

use App\Exceptions\SalonPausedException;
use App\Models\Salon;
use App\Models\User;
use App\Services\SuperAdmin\SuperAdminAudit;
use App\Support\CurrentSalon;
use Carbon\CarbonInterface;

/**
 * بسته‌ی «ح»، ۸ (۲۰۲۶-۱۰-۲۰) — توقف موقت رزرو سالن (تصمیم‌های ۳-۱ تا ۳-۶).
 *
 * فقط «کار تازه» بسته می‌شود: نوبت آنلاین/اپ، پرداخت نوبت، جابه‌جایی، خرید و رزرو جلسه‌ی دوره، نوبت دستی مدیر/متخصص. نوبت‌های ثبت‌شده
 * انجام می‌شوند، یادآوری و پیامک‌ها مثل همیشه، ورود و دیدن نوبت‌ها آزاد، و زمان اشتراک ادامه پیدا می‌کند.
 * توقف ابرمدیر (platform) را مدیر سالن نمی‌تواند باز کند؛ هر دو کار ابرمدیر در گزارش کارهایش ثبت می‌شود.
 */
class SalonPauseService
{
    public function __construct(private readonly SuperAdminAudit $audit) {}

    public function pause(Salon $salon, User $by, string $pausedBy, ?string $reason = null, ?CarbonInterface $until = null): void
    {
        if ($pausedBy === Salon::PAUSED_BY_OWNER && $salon->isPausedByPlatform()) {
            throw new SalonPauseException('رزرو این سالن را مدیریت ماهرو متوقف کرده است؛ برای تغییر با پشتیبانی تماس بگیرید.');
        }

        $salon->forceFill([
            'paused_at' => $salon->isBookingPaused() ? $salon->paused_at : now(),
            'paused_until' => $until,
            'pause_reason' => filled($reason) ? mb_substr(trim($reason), 0, 200) : null,
            'paused_by' => $pausedBy,
            'paused_by_user_id' => $by->id,
        ])->save();

        if ($pausedBy === Salon::PAUSED_BY_PLATFORM) {
            $this->audit->record('salon.paused', sprintf('توقف رزرو سالن «%s»%s%s', $salon->name,
                $until ? ' تا '.verta($until)->format('Y/m/d') : '', filled($reason) ? ' — '.$reason : ''), $salon);
        }
    }

    public function resume(Salon $salon, string $resumedBy): void
    {
        if ($resumedBy === Salon::PAUSED_BY_OWNER && $salon->isPausedByPlatform()) {
            throw new SalonPauseException('رزرو این سالن را مدیریت ماهرو متوقف کرده است؛ برای بازکردن با پشتیبانی تماس بگیرید.');
        }

        $wasPaused = $salon->paused_at !== null;
        $this->clear($salon);

        if ($wasPaused && $resumedBy === Salon::PAUSED_BY_PLATFORM) {
            $this->audit->record('salon.resumed', sprintf('بازکردن رزرو سالن «%s»', $salon->name), $salon);
        }
    }

    /** نگهبان کار تازه؛ سالن داده نشود = سالن جاری درخواست */
    public static function assertOpen(Salon|int|null $salon = null): void
    {
        $salon = match (true) {
            $salon instanceof Salon => $salon,
            is_int($salon) => Salon::find($salon),
            default => app(CurrentSalon::class)->get(),
        };

        if ($salon?->isBookingPaused()) {
            throw SalonPausedException::for($salon);
        }
    }

    private function clear(Salon $salon): void
    {
        $salon->forceFill(['paused_at' => null, 'paused_until' => null, 'pause_reason' => null, 'paused_by' => null, 'paused_by_user_id' => null])->save();
    }
}
