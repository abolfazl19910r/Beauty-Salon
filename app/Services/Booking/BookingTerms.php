<?php

namespace App\Services\Booking;

/**
 * شرایط یک نوبت آنلاین تازه (BookingPaymentPolicy::termsFor):
 * - prepaymentRequired: برچسب «با پیش‌پرداخت» (pending_payment تا پرداخت، لغو خودکار بعد از مهلت).
 * - prepaymentAmount: مبلغ پیش‌پرداخت (بدون پیش‌پرداخت = ۰).
 * - forcePending: تأیید دستی اجباری حتی با متخصص تأیید خودکار (مشتری بدقول در سالن بی درگاه).
 */
final class BookingTerms
{
    public function __construct(
        public readonly bool $prepaymentRequired,
        public readonly float $prepaymentAmount,
        public readonly bool $forcePending = false,
    ) {}

    public function initialStatus(bool $autoConfirm): string
    {
        if ($this->prepaymentRequired) {
            return 'pending_payment';
        }

        return $autoConfirm && ! $this->forcePending ? 'confirmed' : 'pending';
    }
}
