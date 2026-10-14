<?php

namespace App\Support\Billing;

use App\Models\Plan;
use App\Models\Salon;

/**
 * قیمت اشتراک سالن (۲۰۲۶-۰۹-۳۰): قیمت پلن شامل تعداد مشخصی متخصص است؛ هر متخصص بیشتر ماهانه مبلغ ثابتی اضافه دارد، با همان
 * نسبت تخفیف بازه‌ی پلن (پلن ۳/۶/۱۲ ماهه نسبت به ماه‌به‌ماهِ همان پلن). مبلغ اضافه به هزار تومان گرد می‌شود.
 *
 * از ۲۰۲۶-۱۰-۱۴ (بسته‌ی پلن‌ها) همه‌ی اعداد از پلن می‌آیند (App\Models\Plan)؛ پلن null = سالن پیش از پلن‌ها و همان config/billing.php.
 */
class SubscriptionPricing
{
    public const PLAN_MONTHS = ['1m' => 1, '3m' => 3, '6m' => 6, '12m' => 12];

    /** روزهای یک ماه در محاسبه‌ی مابه‌التفاوت ارتقا */
    public const DAYS_PER_MONTH = 30;

    public function includedSpecialists(?Plan $plan = null): int
    {
        return max(1, (int) ($plan ? $plan->included_specialists : config('billing.included_specialists', 10)));
    }

    public function extraSpecialistPricePerMonth(?Plan $plan = null): int
    {
        return max(0, (int) ($plan ? $plan->extra_specialist_price_per_month : config('billing.extra_specialist_price_per_month', 0)));
    }

    public function sells(string $subscriptionType, ?Plan $plan = null): bool
    {
        if (! isset(self::PLAN_MONTHS[$subscriptionType])) {
            return false;
        }

        return $plan ? $plan->priceFor($subscriptionType) !== null : config("billing.subscription_prices.{$subscriptionType}") !== null;
    }

    public function planPrice(string $subscriptionType, ?Plan $plan = null): int
    {
        $price = ! isset(self::PLAN_MONTHS[$subscriptionType]) ? null
            : ($plan ? $plan->priceFor($subscriptionType) : config("billing.subscription_prices.{$subscriptionType}"));

        if ($price === null) {
            throw new \InvalidArgumentException("قیمت برای نوع اشتراک نامعتبر: {$subscriptionType}");
        }

        return (int) $price;
    }

    public function extraSpecialists(int $specialists, ?Plan $plan = null): int
    {
        return max(0, $specialists - $this->includedSpecialists($plan));
    }

    public function extraPrice(string $subscriptionType, int $specialists, ?Plan $plan = null): int
    {
        $extra = $this->extraSpecialists($specialists, $plan);

        if ($extra === 0) {
            return 0;
        }

        $months = self::PLAN_MONTHS[$subscriptionType];
        $price = $this->planPrice($subscriptionType, $plan);
        $monthly = $plan ? ($plan->priceFor('1m') ?? (int) round($price / $months)) : (int) config('billing.subscription_prices.1m', $price);
        $ratio = $monthly > 0 ? $price / ($monthly * $months) : 1;

        return (int) round($extra * $this->extraSpecialistPricePerMonth($plan) * $months * $ratio / 1000) * 1000;
    }

    public function price(string $subscriptionType, int $specialists, ?Plan $plan = null): int
    {
        return $this->planPrice($subscriptionType, $plan) + $this->extraPrice($subscriptionType, $specialists, $plan);
    }

    /**
     * قیمت ماهانه‌ی معادل یک پلن برای بازه‌ی فعلی سالن (همان بازه اگر پلن آن را می‌فروشد، وگرنه ماه‌به‌ماه، وگرنه کوتاه‌ترین بازه‌ای
     * که دارد)؛ null = پلن هیچ بازه‌ای نمی‌فروشد.
     */
    public function monthlyEquivalent(?Plan $plan, string $subscriptionType, int $specialists): ?float
    {
        foreach (array_unique([$subscriptionType, ...array_keys(self::PLAN_MONTHS)]) as $type) {
            if ($this->sells($type, $plan)) {
                return $this->price($type, $specialists, $plan) / self::PLAN_MONTHS[$type];
            }
        }

        return null;
    }

    /**
     * مابه‌التفاوت ارتقای وسط دوره (تصمیم ۳، گزینه‌ی الف): (قیمت روزانه‌ی پلن جدید − قیمت روزانه‌ی پلن فعلی) × روزهای مانده، با
     * همان تعداد متخصص و همان بازه‌ی سالن، گرد به هزار تومان. پایان دوره عوض نمی‌شود. ۰ یا منفی = ارتقا نیست.
     */
    public function upgradePrice(Salon $salon, Plan $to): int
    {
        if (! $salon->subscription_ends_at?->isFuture()) {
            return 0;
        }

        $type = (string) $salon->subscription_type;
        $specialists = (int) $salon->max_specialists_count;
        $current = $this->monthlyEquivalent($salon->effectivePlan(), $type, $specialists);
        $new = $this->monthlyEquivalent($to, $type, $specialists);

        if ($current === null || $new === null) {
            return 0;
        }

        $days = $this->remainingDays($salon);
        $diff = ($new - $current) / self::DAYS_PER_MONTH * $days;

        return max(0, (int) round($diff / 1000) * 1000);
    }

    public function remainingDays(Salon $salon): int
    {
        $ends = $salon->subscription_ends_at;

        return $ends && $ends->isFuture() ? (int) ceil(now()->diffInSeconds($ends) / 86400) : 0;
    }
}
