<?php

namespace App\Observers;

use App\Models\DiscountCode;
use App\Services\ReportCacheService;
use App\Services\SMSService;

class DiscountCodeObserver
{
    protected ReportCacheService $cacheService;
    protected SMSService $smsService;

    public function __construct(ReportCacheService $cacheService, SMSService $smsService)
    {
        $this->cacheService = $cacheService;
        $this->smsService = $smsService;
    }

    public function created(DiscountCode $discountCode): void
    {
        if ($discountCode->user_id && $discountCode->user && $discountCode->user->phone) {
            $message = sprintf(
                "یک کد تخفیف جدید برای شما ایجاد شد:
کد: %s
مقدار: %s%s
مهلت استفاده: %s",
                $discountCode->code,
                $discountCode->amount,
                $discountCode->type === 'percentage' ? '%' : ' تومان',
                $discountCode->expires_at ? verta($discountCode->expires_at)->format('Y/m/d') : 'نامحدود'
            );

            $this->smsService->send($discountCode->user->phone, $message);
        }

        $this->cacheService->flush();
    }

    public function updated(DiscountCode $discountCode): void
    {
        if ($discountCode->max_uses && $discountCode->used_count >= $discountCode->max_uses) {
            if ($discountCode->is_active) {
                $discountCode->is_active = false;
                $discountCode->saveQuietly();
            }

            if ($discountCode->user_id && $discountCode->user && $discountCode->user->phone) {
                $message = sprintf(
                    "کد تخفیف %s به حداکثر استفاده رسید و منقضی شد.",
                    $discountCode->code
                );

                $this->smsService->send($discountCode->user->phone, $message);
            }
        }

        $this->cacheService->flush();
    }

    public function deleted(DiscountCode $discountCode): void
    {
        $this->cacheService->flush();
    }

    public function restored(DiscountCode $discountCode): void
    {
        $this->cacheService->flush();
    }

    public function forceDeleted(DiscountCode $discountCode): void
    {
        $this->cacheService->flush();
    }
}
