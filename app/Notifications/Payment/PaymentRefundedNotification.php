<?php

namespace App\Notifications\Payment;

use App\Models\Salon;
use App\Services\SMSService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * ⭐ پیامک «پول شما برگشت داده شد» به مشتری (تصمیم ابوالفضل ۲۰۲۶-۰۹-۲۶: مشتری حتماً باید مطلع بشه).
 *
 * هر جا پول بدون نوبت/شارژ برمی‌گرده این پیامک فرستاده می‌شه:
 * - slot_taken: ساعت نوبت وقتی مشتری در بانک بود رزرو شد (LostSlotRefundService) → کارت و/یا کیف پول
 * - verify_unanswered: پاسخ تایید بانک (سامان/ملت) نرسید و payments:reconcile برگشت زد → کارت
 * - amount_mismatch: مبلغ تاییدشده‌ی سامان با سفارش یکی نبود و همون لحظه Reverse شد → کارت
 * - settle_failed: ملت پرداخت رو تایید کرد ولی واریز (settle) انجام نشد و همون لحظه برگشت خورد → کارت
 * - verify_unanswered با کیف پول / charge_recovered: پاسخ تایید درگاه غیرمستقیم نرسید و reconcile بعداً دید تایید شده بود
 *
 * ⚠️ عمداً بدون salon_id فرستاده می‌شه (از سهمیه‌ی پیامک سالن کم نمی‌کنه): پیام مالی است و نباید به‌خاطر تمام شدن
 * سهمیه‌ی سالن به مشتری نرسه. صف‌دار (queue worker لازمه)؛ متن همین‌جا ساخته می‌شه، نه در worker.
 */
class PaymentRefundedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const REASONS = ['slot_taken', 'verify_unanswered', 'amount_mismatch', 'settle_failed', 'charge_recovered'];

    public readonly string $text;

    public function __construct(
        public readonly string $reason,
        public readonly int $cardToman,
        public readonly int $walletToman,
        ?Salon $salon = null,
        public readonly ?int $bookingId = null,
    ) {
        $this->text = $this->build($salon?->name);
    }

    public function via(object $notifiable): array
    {
        return filled($notifiable->phone ?? null) && ($this->cardToman > 0 || $this->walletToman > 0) ? ['sms'] : [];
    }

    private function build(?string $salonName): string
    {
        $subject = $this->bookingId ? "پرداخت شما برای نوبت #{$this->bookingId}" : 'پرداخت شما';

        $lines = [$salonName ?: config('brand.name')];
        $lines[] = match ($this->reason) {
            'slot_taken' => "ساعت انتخابی هنگام پرداخت رزرو شده بود و {$subject} ثبت نشد.",
            'verify_unanswered' => "تایید {$subject} از بانک دریافت نشد و ثبت نشد.",
            'settle_failed' => "واریز نهایی {$subject} در بانک انجام نشد و ثبت نشد.",
            'charge_recovered' => 'تایید شارژ کیف پول شما با تأخیر از درگاه رسید و شارژ انجام شد.',
            default => "مبلغ {$subject} با مبلغ سفارش همخوانی نداشت و ثبت نشد.",
        };

        if ($this->cardToman > 0) {
            $lines[] = self::toman($this->cardToman).' تومان به کارت بانکی شما برگشت داده شد (طبق روال بانک معمولاً تا ۷۲ ساعت).';
        }
        if ($this->walletToman > 0) {
            $lines[] = $this->reason === 'charge_recovered'
                ? self::toman($this->walletToman).' تومان به کیف پول شما در همین سالن اضافه شد.'
                : self::toman($this->walletToman).' تومان به کیف پول شما در همین سالن برگشت داده شد و برای رزرو بعدی قابل استفاده است.';
        }

        return implode("\n", $lines);
    }

    private static function toman(int $amount): string
    {
        return strtr(number_format($amount), ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹', ',' => '٬']);
    }

    public function toSms(object $notifiable): bool
    {
        return (new SMSService)->send((string) $notifiable->phone, $this->text);
    }
}
