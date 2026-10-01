<?php

namespace App\Support\Billing;

/**
 * قیمت اشتراک سالن (۲۰۲۶-۰۹-۳۰): قیمت پلن (config billing.subscription_prices) شامل تعداد مشخصی متخصص است
 * (billing.included_specialists)؛ هر متخصص بیشتر ماهانه billing.extra_specialist_price_per_month تومان اضافه دارد، با همان
 * نسبت تخفیف بازه‌ی پلن (پلن ۳/۶/۱۲ ماهه نسبت به ماه‌به‌ماه). مبلغ اضافه به هزار تومان گرد می‌شود.
 */
class SubscriptionPricing
{
    public const PLAN_MONTHS = ['1m' => 1, '3m' => 3, '6m' => 6, '12m' => 12];

    public function includedSpecialists(): int
    {
        return max(1, (int) config('billing.included_specialists', 10));
    }

    public function extraSpecialistPricePerMonth(): int
    {
        return max(0, (int) config('billing.extra_specialist_price_per_month', 0));
    }

    public function planPrice(string $subscriptionType): int
    {
        $price = config("billing.subscription_prices.{$subscriptionType}");

        if ($price === null || ! isset(self::PLAN_MONTHS[$subscriptionType])) {
            throw new \InvalidArgumentException("قیمت برای نوع اشتراک نامعتبر: {$subscriptionType}");
        }

        return (int) $price;
    }

    public function extraSpecialists(int $specialists): int
    {
        return max(0, $specialists - $this->includedSpecialists());
    }

    public function extraPrice(string $subscriptionType, int $specialists): int
    {
        $extra = $this->extraSpecialists($specialists);

        if ($extra === 0) {
            return 0;
        }

        $months = self::PLAN_MONTHS[$subscriptionType];
        $plan = $this->planPrice($subscriptionType);
        $monthly = (int) config('billing.subscription_prices.1m', $plan);
        $ratio = $monthly > 0 ? $plan / ($monthly * $months) : 1;

        return (int) round($extra * $this->extraSpecialistPricePerMonth() * $months * $ratio / 1000) * 1000;
    }

    public function price(string $subscriptionType, int $specialists): int
    {
        return $this->planPrice($subscriptionType) + $this->extraPrice($subscriptionType, $specialists);
    }
}
