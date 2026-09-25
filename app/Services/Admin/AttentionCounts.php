<?php

namespace App\Services\Admin;

use App\Models\PaymentTransaction;
use App\Models\WithdrawalRequest;
use App\Support\CurrentSalon;

/**
 * ⭐ شمارنده‌ی «نیاز به بررسی انسانی» برای منوی مدیریت و داشبورد (۲۰۲۶-۰۹-۲۶). هر بار شمرده می‌شه (count روی ستون
 * index‌دار) — عمداً بدون حافظه: نمونه‌ی ماندگار (صف، Octane، تست) عدد کهنه نشون می‌داد.
 * هر دو شمارش به سالن فعلی محدودند: تراکنش‌ها با salon_id صریح (مدل scope نداره)، برداشت‌ها از طریق متخصص سالن.
 */
class AttentionCounts
{
    public function __construct(private readonly CurrentSalon $current) {}

    public function payments(): int
    {
        $salon = $this->current->get();

        return $salon ? PaymentTransaction::where('salon_id', $salon->id)->where('needs_attention', true)->count() : 0;
    }

    public function withdrawals(): int
    {
        return WithdrawalRequest::query()->whereHas('specialist')->where('needs_manual_check', true)->count();
    }

    public function total(): int
    {
        return $this->payments() + $this->withdrawals();
    }
}
