<?php

namespace App\Http\Requests\Admin\SalonSettings;

use App\Http\Requests\Concerns\ValidatesSalonContactDetails;
use App\Services\Salon\SalonLogoService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * ⭐ صفحه‌ی «اطلاعات سالن» مالک سالن (۲۰۲۶-۰۹-۲۴). همون قوانین فرم ثبت‌نام/سوپرادمین
 * (ValidatesSalonContactDetails + SalonLogoService::RULES)؛ آدرس/تلفن/سابقه اختیاری‌ان تا
 * سالن‌های قدیمی که هنوز این‌ها رو ندارن بتونن فقط یک فیلد رو ذخیره کنن.
 */
class UpdateSalonSettingsRequest extends FormRequest
{
    use ValidatesSalonContactDetails;

    public function authorize(): bool
    {
        return true; // مسیر پشت middleware salon.owner است
    }

    protected function prepareForValidation(): void
    {
        $this->prepareSalonContactInput();
    }

    public function after(): array
    {
        return [fn ($validator) => $this->validateSalonWorkingHours($validator)];
    }

    public function rules(): array
    {
        return $this->salonContactRules(required: false) + [
            'name' => ['required', 'string', 'max:255'],
            'sms_name' => ['nullable', 'string', 'max:20'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'logo' => SalonLogoService::RULES,
            'remove_logo' => ['nullable', 'boolean'],
            'logo_dark' => SalonLogoService::DARK_RULES,
            'remove_logo_dark' => ['nullable', 'boolean'],
            'cover' => \App\Services\Salon\SalonCoverService::RULES,
            'remove_cover' => ['nullable', 'boolean'],
            // بسته‌ی ۴ ظاهری: مکان روی نقشه برای «مسیریابی» مشتری (اختیاری) — لینک اشتراک‌گذاری (کوتاه یا کامل) گوگل‌مپ/نشان/بلد یا «عرض، طول»
            'map_location' => ['nullable', 'string', 'max:1000', function ($attribute, $value, $fail) {
                if ($error = \App\Support\SalonMapLocation::error($value)) {
                    $fail($error);
                }
            }],
        ];
    }

    /** @return array{map_url: string|null, map_lat: float|null, map_lng: float|null}|array{} فقط وقتی فیلد در فرم بود */
    public function mapLocationAttributes(): array
    {
        if (! $this->exists('map_location')) {
            return [];
        }

        return \App\Support\SalonMapLocation::attributesFor($this->input('map_location'));
    }

    public function messages(): array
    {
        return $this->salonContactMessages() + SalonLogoService::MESSAGES + SalonLogoService::DARK_MESSAGES + \App\Services\Salon\SalonCoverService::MESSAGES + [
            'name.required' => 'نام سالن را وارد کنید.',
        ];
    }
}
