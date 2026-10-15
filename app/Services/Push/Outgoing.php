<?php

namespace App\Services\Push;

use App\Support\Features\Feature;

/**
 * یک پیام به یک نفر که هم می‌تواند پوش اپ باشد هم پیامک (بسته‌ی ۴). متن پیامک همان متن پوش است.
 *
 * - $pushKey: کلید سیاست پوش (دسته و عنوان؛ PushPolicy) — معمولاً همان کلید رویداد تنظیمات اعلان.
 * - $settingsEvent: رویدادی که سوییچ «پیامک» و «اپ» آن در تنظیمات اعلان سالن ملاک است؛ null = بدون سوییچ (مثل یادآوری نوبت).
 * - $inbox: پیام در صندوق اعلان اپ هم ثبت شود (تصمیم ۵: پیام‌هایی که تا امروز فقط پیامک بودند).
 */
final class Outgoing
{
    /** @param array<string, scalar|null> $data شناسه‌ها برای باز کردن صفحه‌ی درست در اپ (booking_id، course_id، ...) */
    public function __construct(
        public readonly ?object $recipient,
        public readonly ?string $phone,
        public readonly string $text,
        public readonly ?int $salonId,
        public readonly ?Feature $smsFeature,
        public readonly string $pushKey,
        public readonly ?string $settingsEvent = null,
        public readonly ?PushUrgency $urgency = null,
        public readonly array $data = [],
        public readonly bool $inbox = false,
        public readonly ?string $title = null,
    ) {}

    public function urgency(): PushUrgency
    {
        return $this->urgency ?? PushPolicy::urgency($this->pushKey);
    }

    public function title(): string
    {
        return $this->title ?? PushPolicy::title($this->pushKey);
    }
}
