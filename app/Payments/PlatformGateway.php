<?php

namespace App\Payments;

use App\Models\PlatformGatewayRequest;

/**
 * درگاه پرداخت پلتفرم برای سالن‌ها (۲۰۲۶-۱۱-۲۲). پرداخت مشتری سالنِ تأییدشده با مرچنت پلتفرم (PlatformZarinpal) گرفته می‌شود؛
 * ردیف درگاه سالن driver = platform دارد (در GatewayCatalog نیست، پس فهرست درگاه‌های مالک، تسویه‌ی متخصص و اتصال زرین‌پال
 * آن را نمی‌بینند) و هر تراکنش آن، درصد کارمزد پلتفرم را همان لحظه روی خودش نگه می‌دارد.
 *
 * سهم پلتفرم = گرد(کل مبلغ دریافتی × درصد ÷ ۱۰۰) به ریال؛ سهم سالن = بقیه. کل مبلغ همان amount_rial تراکنش است (مبلغی که از
 * مشتری گرفته شد).
 */
final class PlatformGateway
{
    public const DRIVER = 'platform';

    /** وضعیت‌هایی که پولشان نزد پلتفرم و متعلق به سالن است: paid، و refunded (به کیف پول مشتری در همان سالن برگشت) */
    public const SETTLEABLE_STATUSES = ['paid', 'refunded'];

    public static function enabled(): bool
    {
        return (bool) config('payments.platform_gateway.enabled', false);
    }

    public static function label(): string
    {
        return (string) config('payments.platform_gateway.label', 'درگاه پرداخت '.config('brand.name'));
    }

    public static function maxFeePercent(): float
    {
        return (float) config('payments.platform_gateway.max_fee_percent', 30);
    }

    /** درصد کارمزد فعال سالن (آخرین درخواست تأییدشده)؛ ۰ اگر نیست */
    public static function feePercentFor(?int $salonId): float
    {
        if (! $salonId) {
            return 0.0;
        }

        return (float) (PlatformGatewayRequest::query()->where('salon_id', $salonId)->where('status', 'approved')
            ->latest('reviewed_at')->latest('id')->value('fee_percent') ?? 0);
    }

    /** سهم پلتفرم به ریال (گرد به نزدیک‌ترین ریال، هرگز بیشتر از کل) */
    public static function platformShare(int $grossRial, float $percent): int
    {
        if ($grossRial <= 0 || $percent <= 0) {
            return 0;
        }

        $basisPoints = (int) round($percent * 100);

        return min($grossRial, intdiv($grossRial * $basisPoints + 5000, 10000));
    }
}
