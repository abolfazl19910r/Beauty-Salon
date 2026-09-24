<?php

namespace App\Payments;

use App\Models\PaymentTransaction;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * ⭐ ثبت «رسید مصرف‌شده» برای درگاه‌هایی که خودشون جلوی استفاده‌ی دوباره از رسید رو نمی‌گیرن
 * (مرحله‌ی ۲ چند درگاه — بانک سامان). سپ هر RefNum رو هر چند بار که بخوای دوباره تایید می‌کنه (کد ۲ =
 * «درخواست تکراری») و پاسخ verify شماره‌ی خرید (ResNum) رو برنمی‌گردونه؛ پس بدون این قفل، رسید یک پرداخت
 * موفق می‌تونست برای تراکنش دیگه‌ای با همون مبلغ هم ارائه بشه.
 *
 * claim() اتمیک است: index یکتای (driver, gateway_receipt) در دیتابیس تصمیم می‌گیره، نه یک SELECT قبلی.
 * - رسید آزاد → به این تراکنش وصل می‌شه → true
 * - همین تراکنش قبلاً همین رسید رو گرفته (callback تکراری / تلاش دوباره) → true
 * - رسید مال تراکنش دیگه‌ایه، یا این تراکنش رسید دیگه‌ای داره → false
 */
final class GatewayReceipt
{
    public static function claim(string $driver, string $receipt, int $transactionId): bool
    {
        $receipt = trim($receipt);

        if ($receipt === '' || mb_strlen($receipt) > 100) {
            return false;
        }

        $transaction = PaymentTransaction::query()->whereKey($transactionId)->first(['id', 'driver', 'gateway_receipt']);

        if (! $transaction || $transaction->driver !== $driver) {
            return false;
        }

        if ($transaction->gateway_receipt !== null) {
            return hash_equals($transaction->gateway_receipt, $receipt);
        }

        try {
            $updated = PaymentTransaction::query()
                ->whereKey($transactionId)
                ->whereNull('gateway_receipt')
                ->update(['gateway_receipt' => $receipt]);
        } catch (UniqueConstraintViolationException) {
            return false; // همین رسید قبلاً به تراکنش دیگه‌ای وصل شده
        }

        if ($updated === 1) {
            return true;
        }

        // یک callback هم‌زمان برای همین تراکنش زودتر رسید؛ فقط اگه همین رسید رو ثبت کرده قبوله
        $current = PaymentTransaction::query()->whereKey($transactionId)->value('gateway_receipt');

        return is_string($current) && hash_equals($current, $receipt);
    }
}
