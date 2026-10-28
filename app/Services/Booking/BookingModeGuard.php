<?php

namespace App\Services\Booking;

use App\Models\BookingPolicy;
use App\Models\Salon;
use App\Notifications\Booking\BookingModeFallbackNotification;
use App\Support\CurrentSalon;
use App\Support\Features\Feature;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * برگشت خودکار به «بدون پیش‌پرداخت» (بسته‌ی 5ه، تصمیم ۲): اگر سالنی «با پیش‌پرداخت» است ولی درگاه فعالش از دست رفت یا پلنش امکان پرداخت
 * آنلاین ندارد، حالت را «بدون پیش‌پرداخت» می‌کند (نوبت‌های در انتظار پرداخت هم مثل تغییر دستی) و به مالک هشدار می‌دهد. رزرو قطع نمی‌شود:
 * حتی پیش از این بررسی، BookingPaymentPolicy نوبت تازه را بی درگاه «بدون پیش‌پرداخت» ثبت می‌کند.
 *
 * صدا زده می‌شود از: تغییر/حذف درگاه سالن (SalonPaymentGatewayObserver) و فرمان زمان‌بندی‌شده‌ی bookings:enforce-payment-mode
 * (تغییر پلن، پایان اشتراک و هر راه دیگری که امکان پرداخت آنلاین را بردارد).
 */
class BookingModeGuard
{
    public function __construct(
        private readonly BookingPaymentPolicy $policy,
        private readonly BookingModeSwitcher $switcher,
    ) {}

    /** @return bool true = همین الان برگشت داده شد */
    public function enforce(Salon $salon): bool
    {
        return app(CurrentSalon::class)->withSalon((int) $salon->id, function () use ($salon) {
            $settings = BookingPolicy::query()->first();
            if (! $settings?->wantsPrepayment() || $this->policy->canPrepay($salon)) {
                return false;
            }

            $reason = $salon->hasFeature(Feature::OnlinePayment)
                ? 'سالن درگاه پرداخت فعالی ندارد.'
                : 'پلن سالن امکان «پرداخت آنلاین مشتری» را ندارد.';

            $waived = $this->switcher->switchTo(BookingPolicy::MODE_NONE, fallback: true);

            $owners = $salon->admins()->wherePivot('role', 'owner')->get();
            if ($owners->isNotEmpty()) {
                Notification::send($owners, new BookingModeFallbackNotification((int) $salon->id, $reason, $waived));
            }

            Log::warning('Booking payment mode fell back to none', ['salon_id' => $salon->id, 'reason' => $reason, 'waived' => $waived]);

            return true;
        });
    }

    /** همه‌ی سالن‌های «با پیش‌پرداخت» @return int تعداد سالن‌های برگشت‌داده */
    public function enforceAll(): int
    {
        $salonIds = app(CurrentSalon::class)->allSalons(fn () => BookingPolicy::query()
            ->where('payment_mode', BookingPolicy::MODE_PREPAY)->pluck('salon_id'));

        $count = 0;
        foreach (Salon::query()->whereKey($salonIds)->get() as $salon) {
            try {
                $count += $this->enforce($salon) ? 1 : 0;
            } catch (\Throwable $e) {
                Log::error('Booking payment mode enforcement failed', ['salon_id' => $salon->id, 'error' => $e->getMessage()]);
            }
        }

        return $count;
    }
}
