<?php

namespace App\Services\Booking;

use App\Exceptions\BookingModeException;
use App\Models\Booking;
use App\Models\BookingPolicy;
use App\Models\WalletSetting;
use App\Notifications\Booking\BookingNotification;
use App\Services\Payment\BookingWalletShare;
use App\Services\Push\AppMessenger;
use App\Services\Push\Outgoing;
use App\Services\Push\PushPolicy;
use App\Support\Features\Feature;
use App\Support\Sms\SmsText;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * تغییر «نحوه‌ی رزرو» سالن جاری (بسته‌ی 5ه، تصمیم‌های ۷ و ۸):
 * - بدون ← با پیش‌پرداخت: فقط نوبت‌های تازه پیش‌پرداخت لازم دارند؛ نوبت‌های قبلی دست نمی‌خورند.
 * - با ← بدون پیش‌پرداخت (دستی یا برگشت خودکار): نوبت‌های pending_payment موجود ← pending با برچسب «بدون پیش‌پرداخت» و پیش‌پرداخت صفر؛
 *   مشتری یک پیام (صندوق اپ، پوش یا پیامک از سهمیه‌ی سالن) و متخصص اعلان و پیامک «نوبت تازه، در انتظار تأیید». هیچ پیامی درخواست
 *   پرداخت نمی‌کند. نوبت‌های پرداخت‌شده برچسب «با پیش‌پرداخت» می‌مانند.
 */
class BookingModeSwitcher
{
    public function __construct(private readonly BookingPaymentPolicy $policy) {}

    /**
     * @return int تعداد نوبت‌هایی که از «در انتظار پرداخت» بیرون آمدند
     *
     * @throws BookingModeException پیش‌نیاز «با پیش‌پرداخت» فراهم نیست
     */
    public function switchTo(string $mode, bool $fallback = false): int
    {
        $settings = BookingPolicy::current();

        if ($mode === BookingPolicy::MODE_PREPAY) {
            $this->assertCanPrepay();
        }

        $changes = ['payment_mode' => $mode];
        if ($fallback) {
            $changes['payment_mode_fallback_at'] = now();
        } elseif ($mode === BookingPolicy::MODE_PREPAY) {
            $changes['payment_mode_fallback_at'] = null;
        }
        $settings->update($changes);

        return $mode === BookingPolicy::MODE_NONE ? $this->waivePendingPrepayments() : 0;
    }

    /** @throws BookingModeException */
    public function assertCanPrepay(?float $percentage = null, ?float $minimum = null): void
    {
        $salon = $this->policy->salon();

        if (! $salon?->hasFeature(Feature::OnlinePayment)) {
            throw BookingModeException::because('پلن سالن شما امکان «پرداخت آنلاین مشتری» را ندارد؛ رزرو با پیش‌پرداخت ممکن نیست.');
        }
        if (! $this->policy->canPrepay($salon)) {
            throw BookingModeException::because('برای رزرو با پیش‌پرداخت، اول حداقل یک درگاه پرداخت فعال اضافه کنید.');
        }

        $wallet = WalletSetting::get();
        if (($percentage ?? (float) $wallet->prepayment_percentage) <= 0 && ($minimum ?? (float) $wallet->minimum_prepayment_amount) <= 0) {
            throw BookingModeException::because('درصد یا حداقل پیش‌پرداخت باید بیشتر از صفر باشد.');
        }
    }

    /** نوبت‌های «در انتظار پرداخت» سالن جاری بدون پیش‌پرداخت می‌شوند */
    public function waivePendingPrepayments(): int
    {
        $ids = Booking::query()
            ->where('status', 'pending_payment')
            ->where('payment_status', 'unpaid')
            ->pluck('id');

        $count = 0;
        foreach ($ids as $id) {
            $booking = $this->waive((int) $id);
            if ($booking) {
                $count++;
                $this->notify($booking);
            }
        }

        return $count;
    }

    private function waive(int $id): ?Booking
    {
        return DB::transaction(function () use ($id) {
            $booking = Booking::query()->whereKey($id)->lockForUpdate()->first();
            if (! $booking || $booking->status !== 'pending_payment' || $booking->payment_status === 'paid') {
                return null;
            }

            // مشتری همین حالا در صفحه‌ی بانک است: سهم کیف پول تلاشش نگه داشته می‌ماند تا نتیجه‌ی درگاه روشن شود (پرداخت موفق هم پذیرفته
            // می‌شود و برچسب «با پیش‌پرداخت» می‌گیرد؛ BookingPaymentFinalizer)
            $paying = ! Booking::query()->whereKey($id)->withoutPaymentInProgress()->exists();

            $booking->update([
                'status' => 'pending',
                'prepayment_required' => false,
                'prepayment_amount' => 0,
            ]);

            if (! $paying) {
                app(BookingWalletShare::class)->releaseForBooking($booking, 'پیش‌پرداخت این نوبت دیگر لازم نیست');
            }

            return $booking;
        });
    }

    private function notify(Booking $booking): void
    {
        $booking->load(['service', 'specialist', 'user']);

        try {
            if ($booking->user?->phone) {
                app(AppMessenger::class)->deliver(new Outgoing(
                    recipient: $booking->user,
                    phone: $booking->user->phone,
                    text: SmsText::bookingPrepaymentWaived($booking),
                    salonId: (int) $booking->salon_id,
                    smsFeature: Feature::SmsCustomer,
                    pushKey: PushPolicy::BOOKING_PREPAYMENT_WAIVED_CUSTOMER,
                    data: ['booking_id' => $booking->id],
                    inbox: true,
                ));
            }
        } catch (\Throwable $e) {
            Log::warning('Prepayment-waived message to customer failed', ['booking_id' => $booking->id, 'error' => $e->getMessage()]);
        }

        try {
            // متخصص پیش از پرداخت از این نوبت خبر نداشت
            $booking->specialist?->notify(new BookingNotification($booking, true));
        } catch (\Throwable $e) {
            Log::warning('Prepayment-waived notice to specialist failed', ['booking_id' => $booking->id, 'error' => $e->getMessage()]);
        }
    }
}
