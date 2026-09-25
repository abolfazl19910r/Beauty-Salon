<?php

namespace App\Payments\Contracts;

use App\Models\PaymentTransaction;

/**
 * ⭐ درگاه‌های مستقیم بانکی که می‌شه کل مبلغ یک تراکنشِ گرفته‌شده رو با API به کارت مشتری برگردوند
 * * (سامان: ReverseTransaction تا ۵۰ دقیقه بعد از تراکنش). دو جا ازش استفاده می‌شه:
 * payments:reconcile (پاسخ تایید نرسید) و LostSlotRefundService (ساعت نوبت از دست رفت).
 *
 * true فقط وقتی بانک برگشت رو تأیید کرد (یا گفت قبلاً برگشت خورده)؛ هر چیز دیگه false، و صدازننده‌ها
 * راه جایگزین (اجرای بعدی reconcile / کیف پول) رو می‌رن.
 */
interface ReversibleGateway
{
    public function reverseTransaction(PaymentTransaction $transaction): bool;
}
