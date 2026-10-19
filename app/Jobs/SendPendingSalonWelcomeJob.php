<?php

namespace App\Jobs;

use App\Models\Salon;
use App\Models\User;
use App\Services\SalonSignup\SalonWelcome;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * مهلت پشتیبان پیامک خوش‌آمد ثبت‌نام با «خرید» (بسته‌ی «ح»، ۳): اگر نتیجه‌ی پرداخت تا ۳۰ دقیقه نیامد (مالک صفحه‌ی بانک را بست)،
 * پیامک با وضعیت همان لحظه (معمولاً آزمایشی) می‌رود. اگر نتیجه زودتر آمده بود، SalonWelcome::sendPending کاری نمی‌کند.
 */
class SendPendingSalonWelcomeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(
        public readonly int $salonId,
        public readonly int $ownerId,
        public readonly string $publicUrl,
        public readonly string $adminUrl,
    ) {}

    public function handle(SalonWelcome $welcome): void
    {
        $salon = Salon::query()->find($this->salonId);
        if (! $salon) {
            return;
        }

        $welcome->sendPending($salon, User::query()->find($this->ownerId), $this->publicUrl, $this->adminUrl);
    }
}
