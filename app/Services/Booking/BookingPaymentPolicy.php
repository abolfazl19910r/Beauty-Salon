<?php

namespace App\Services\Booking;

use App\Models\BookingPolicy;
use App\Models\Salon;
use App\Models\User;
use App\Models\WalletSetting;
use App\Support\CurrentSalon;

/**
 * نحوه‌ی رزرو مؤثر سالن و شرایط پرداخت یک نوبت تازه (بسته‌ی 5ه، ۲۰۲۶-۱۰-۲۸).
 *
 * - «با پیش‌پرداخت» فقط وقتی مؤثر است که سالن امکان پرداخت آنلاین پلن + حداقل یک درگاه فعال داشته باشد (Salon::acceptsOnlinePayments)
 *   و مبلغ محاسبه‌شده بیشتر از صفر باشد؛ وگرنه نوبت «بدون پیش‌پرداخت» ثبت می‌شود (هیچ‌وقت نوبتی که پرداختش ممکن نیست).
 * - برچسب نوبت (bookings.prepayment_required) از همین‌جا می‌آید و بعد از ثبت دیگر به حالت سالن نگاه نمی‌شود.
 */
class BookingPaymentPolicy
{
    public function salon(): ?Salon
    {
        return app(CurrentSalon::class)->get();
    }

    public function canPrepay(?Salon $salon = null): bool
    {
        $salon ??= $this->salon();

        return (bool) $salon?->acceptsOnlinePayments();
    }

    /** حالت مؤثر سالن جاری: prepay فقط با تنظیم مدیر + درگاه؛ بقیه none */
    public function effectiveMode(): string
    {
        return BookingPolicy::current()->wantsPrepayment() && $this->canPrepay()
            ? BookingPolicy::MODE_PREPAY
            : BookingPolicy::MODE_NONE;
    }

    public function prepaymentMode(): bool
    {
        return $this->effectiveMode() === BookingPolicy::MODE_PREPAY;
    }

    /**
     * شرایط نوبت آنلاین تازه‌ی این مشتری برای خدمتی با این قیمت.
     */
    public function termsFor(?User $customer, float $servicePrice): BookingTerms
    {
        $amount = (float) WalletSetting::get()->calculatePrepaymentAmount($servicePrice);
        $prepay = $amount > 0 && $this->prepaymentMode();

        return new BookingTerms(
            prepaymentRequired: $prepay,
            prepaymentAmount: $prepay ? $amount : 0.0,
            forcePending: false,
        );
    }
}
