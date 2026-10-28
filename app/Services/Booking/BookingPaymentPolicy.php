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
        $possible = $amount > 0 && $this->canPrepay();
        $prepay = $possible && BookingPolicy::current()->wantsPrepayment();

        // مشتری بدقول (تصمیم ۱۶): با درگاه ← فقط با پیش‌پرداخت؛ بی درگاه ← رزرو بسته نمی‌شود، تأیید دستی اجباری (اگر مدیر خاموشش نکرده)
        if (! $prepay && $customer && app(NoShowRecord::class)->isFlagged($customer)) {
            return $possible
                ? new BookingTerms(true, $amount, false, 'no_show')
                : new BookingTerms(false, 0.0, (bool) BookingPolicy::current()->flagged_force_pending, 'no_show');
        }

        return new BookingTerms(
            prepaymentRequired: $prepay,
            prepaymentAmount: $prepay ? $amount : 0.0,
            forcePending: false,
        );
    }

    /** محافظ‌های مشتری بدقول در سالنی که پیش‌پرداخت از او ممکن نیست (سقف کمتر نوبت فعال) فعال‌اند؟ */
    public function flaggedWithoutPrepayment(User|int|null $customer, float $servicePrice = 1.0): bool
    {
        $customer = is_int($customer) ? User::find($customer) : $customer;
        if (! $customer || ! app(NoShowRecord::class)->isFlagged($customer)) {
            return false;
        }

        return ! $this->termsFor($customer, $servicePrice)->prepaymentRequired;
    }
}
