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
            'tagline' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'logo' => SalonLogoService::RULES,
            'remove_logo' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return $this->salonContactMessages() + SalonLogoService::MESSAGES + [
            'name.required' => 'نام سالن را وارد کنید.',
        ];
    }
}
