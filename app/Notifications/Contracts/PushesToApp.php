<?php

namespace App\Notifications\Contracts;

/**
 * اعلانی که پیامکش (toSms) برای کاربر اپ به پوش تبدیل می‌شود (بسته‌ی ۴). SmsChannel متن پیامک را از toSms() می‌گیرد
 * (بدون فرستادن) و به AppMessenger می‌دهد؛ دسته و عنوان از PushPolicy با pushKey().
 */
interface PushesToApp
{
    /** کلید رویداد تنظیمات اعلان (سوییچ «اپ») و سیاست پوش */
    public function pushKey(object $notifiable): string;

    /** @return array<string, scalar|null> شناسه‌ها برای باز کردن صفحه‌ی درست در اپ */
    public function pushData(): array;
}
