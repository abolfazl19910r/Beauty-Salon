<?php

namespace App\Observers;

use App\Models\DiscountCode;
use App\Services\ReportCacheService;
use App\Services\SMSService;

class DiscountCodeObserver
{
    public function __construct(protected readonly ReportCacheService $cacheService, protected readonly SMSService $smsService) {}

    public function created(DiscountCode $discountCode): void
    {
        if ($discountCode->user_id && $discountCode->user && $discountCode->user->phone) {
            $message = \App\Support\Sms\SmsText::discountCodeIssued(
                (string) $discountCode->code,
                $discountCode->amount.($discountCode->type === 'percentage' ? '٪' : ' تومان'),
                $discountCode->expires_at,
                \App\Models\Salon::withoutGlobalScopes()->find($discountCode->salon_id)
            );

            $this->smsService->send($discountCode->user->phone, $message, $discountCode->salon_id);
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
                $message = \App\Support\Sms\SmsText::discountCodeUsedUp((string) $discountCode->code, \App\Models\Salon::withoutGlobalScopes()->find($discountCode->salon_id));

                $this->smsService->send($discountCode->user->phone, $message, $discountCode->salon_id);
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
