<?php

namespace App\Http\Requests\Admin\Gallery;

use App\Models\BeautyService;
use App\Models\Specialist;
use Closure;

/**
 * قواعد پیوند اختیاری نمونه‌کار به خدمت و کارشناس (بسته‌ی ۵ب). هر دو با scope سراسری سالن جست‌وجو می‌شوند، پس
 * شناسه‌ی سالن دیگر پذیرفته نمی‌شود. اگر هر دو انتخاب شوند، کارشناس باید همان خدمت را انجام دهد؛ وگرنه دکمه‌ی
 * «این خدمت را با همین کارشناس رزرو کنید» به فرمی می‌رسید که این ترکیب را ندارد.
 */
trait GalleryLinkRules
{
    protected function linkRules(): array
    {
        return [
            'service_id' => ['nullable', 'integer', function (string $attribute, mixed $value, Closure $fail) {
                if (! BeautyService::query()->whereKey($value)->exists()) {
                    $fail('خدمت انتخاب‌شده در این سالن نیست.');
                }
            }],
            'specialist_id' => ['nullable', 'integer', function (string $attribute, mixed $value, Closure $fail) {
                $specialist = Specialist::query()->find($value);
                if (! $specialist) {
                    $fail('کارشناس انتخاب‌شده در این سالن نیست.');

                    return;
                }
                $serviceId = $this->input('service_id');
                if ($serviceId && ! $specialist->services()->whereKey($serviceId)->exists()) {
                    $fail('این کارشناس خدمت انتخاب‌شده را انجام نمی‌دهد.');
                }
            }],
        ];
    }
}
