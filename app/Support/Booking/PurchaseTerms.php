<?php

namespace App\Support\Booking;

use App\Models\Salon;
use App\Models\WalletSetting;
use App\Services\Api\CustomerPresenter;

/**
 * اطلاعات پیش از خرید و رسید نوبت (بسته‌ی مجوز 5د؛ مواد ۳۳ تا ۳۵ قانون تجارت الکترونیکی): هویت و نشانی ارائه‌دهنده (سالن)، شرایط
 * لغو و برگشت پیش‌پرداخت، مهلت پرداخت و راه شکایت — یک منبع برای صفحه‌ی تأیید رزرو، صفحه‌ی موفقیت، صفحه‌ی نوبت («نوبت‌های من»،
 * واسط بادوام) و API اپ. متن‌ها از تنظیمات واقعی همان سالن ساخته می‌شوند، نه متن ثابت.
 */
final class PurchaseTerms
{
    /** نوبت پرداخت‌نشده بعد از این مدت آزاد می‌شود (App\Jobs\CancelUnpaidBookings) */
    public const UNPAID_HOLD_MINUTES = 30;

    /** @return array{name: string, address: ?string, phone: ?string, platform: string} */
    public static function provider(Salon $salon): array
    {
        return [
            'name' => (string) $salon->name,
            'address' => $salon->address ?: null,
            'phone' => $salon->phone ?: null,
            'platform' => (string) config('brand.name'),
        ];
    }

    /**
     * @param  bool|null  $prepay  برچسب نوبت (رسید) یا حالت رزرو (پیش از خرید)؛ null = حالت مؤثر فعلی سالن (بسته‌ی 5ه)
     * @return list<string>
     */
    public static function cancellation(?WalletSetting $settings = null, ?bool $prepay = null): array
    {
        $settings ??= WalletSetting::get();
        $prepay ??= self::prepaymentMode();
        $deadline = CustomerPresenter::CANCEL_DEADLINE_HOURS;
        $feeHours = (int) $settings->cancellation_before_hours;
        $fee = (float) $settings->customer_cancellation_fee_percentage;

        $lines = ['لغو یا جابه‌جایی توسط شما از «نوبت‌های من» تا '.to_persian_num((string) $deadline).' ساعت پیش از زمان نوبت ممکن است؛ بعد از آن با سالن تماس بگیرید.'];

        if (! $prepay) {
            $lines[] = 'این نوبت پیش‌پرداخت آنلاین ندارد: کل مبلغ خدمت (پس از کسر تخفیف) را در سالن پرداخت می‌کنید و لغو در این مهلت هزینه‌ای ندارد.';
            $lines[] = 'نوبت پس از تأیید سالن قطعی می‌شود؛ تأیید یا لغو آن را با پیامک یا اعلان اپ خبر می‌دهیم.';

            return $lines;
        }

        if ($fee > 0 && $feeHours > $deadline) {
            $lines[] = 'لغو کمتر از '.to_persian_num((string) $feeHours).' ساعت پیش از نوبت: '.to_persian_num(rtrim(rtrim(number_format($fee, 2, '.', ''), '0'), '.'))
                .'٪ پیش‌پرداخت جریمه کم و بقیه به کیف پول شما در همین سالن برمی‌گردد؛ زودتر از آن، کل پیش‌پرداخت.';
        } else {
            $lines[] = 'با لغو در این مهلت، کل پیش‌پرداخت به کیف پول شما در همین سالن برمی‌گردد و در رزرو بعدی استفاده می‌شود.';
        }

        $lines[] = 'اگر سالن نوبت را لغو کند، کل پیش‌پرداخت به کیف پول شما برمی‌گردد.';
        $lines[] = 'نوبتی که تا '.to_persian_num((string) self::UNPAID_HOLD_MINUTES).' دقیقه پرداخت نشود، خودکار آزاد می‌شود.';

        return $lines;
    }

    /** نحوه‌ی رزرو مؤثر سالن جاری: true = با پیش‌پرداخت آنلاین (بسته‌ی 5ه) */
    public static function prepaymentMode(): bool
    {
        return app(\App\Services\Booking\BookingPaymentPolicy::class)->prepaymentMode();
    }

    /** برای API اپ */
    public static function forApi(Salon $salon, ?bool $prepay = null): array
    {
        $prepay ??= self::prepaymentMode();

        return [
            'provider' => self::provider($salon),
            // بسته‌ی 5ه: none = پرداخت کامل در سالن، prepay = پیش‌پرداخت آنلاین
            'booking_mode' => $prepay ? 'prepay' : 'none',
            'cancellation' => self::cancellation(null, $prepay),
            'complaints' => 'شکایت: پروفایل ← شکایت و پیگیری (پاسخ حداکثر ظرف '.to_persian_num((string) config('legal.complaint_response_hours', 72)).' ساعت)',
            'terms_url' => \App\Support\CentralUrl::route('legal.terms'),
        ];
    }
}
