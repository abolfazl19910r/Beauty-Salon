<?php

namespace App\Exceptions;

use LogicException;

/**
 * کوئری روی مدل سالن‌دار بدون سالن جاری (حالت سخت‌گیر BelongsToSalon، ۲۰۲۶-۱۰-۰۱). قبلاً چنین کوئری‌ای بی‌صدا همه‌ی
 * سالن‌ها را می‌خواند/می‌نوشت (مثلاً WalletSetting::get() ردیف اولین سالن را برمی‌گرداند). راه درست: سالن را ست کن
 * (CurrentSalon::set / scoped)، یا اگر واقعاً همه‌ی سالن‌ها منظور است، صریح بگو: CurrentSalon::allSalons(fn () => ...).
 */
class MissingSalonContextException extends LogicException
{
    public static function forModel(string $model): self
    {
        return new self("Query on salon-scoped model {$model} without a current salon. Set the salon or wrap the code in CurrentSalon::allSalons().");
    }
}
