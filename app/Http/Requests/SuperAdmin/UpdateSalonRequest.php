<?php

namespace App\Http\Requests\SuperAdmin;

use App\Http\Requests\Concerns\ValidatesSalonContactDetails;
use Illuminate\Foundation\Http\FormRequest;

/**
 * ⭐ slug is deliberately NOT editable here — immutable by design (see the "Migration 1 —
 * جدول salons" section of Rasta_unified_prompt.md: renaming a salon's display name shouldn't
 * ever break a bookmarked/SMS'd link to its slug).
 */
class UpdateSalonRequest extends FormRequest
{
    use ValidatesSalonContactDetails;

    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super-admin');
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
        // ⭐ ۲۰۲۶-۰۹-۲۳: اطلاعات تماس/فعالیت سالن اینجا اختیاریه (سالن‌های موجود هنوز ندارن).
        return $this->salonContactRules(required: false) + [
            // ⭐ لوگوی اختصاصی سالن (۲۰۲۶-۰۹-۲۴) — اختیاری؛ remove_logo فقط در ویرایش معنی داره.
            'logo' => \App\Services\Salon\SalonLogoService::RULES,
            'remove_logo' => ['nullable', 'boolean'],
            'name' => ['required', 'string', 'max:255'],
            // ⭐ پیگیری «محور ۳» (۲۰۲۶-۰۹-۲۰) — متن‌های بازاریابی per-salon، به‌جای ثابت/generic.
            'tagline' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'max_specialists_count' => ['required', 'integer', 'min:0'],
            'module_permissions' => ['nullable', 'array'],
            'module_permissions.*' => ['string'],
            // ⭐ فاز ۲، مورد ۹ («مرچنت آیدی مجزا برای هر سالن») — اختیاری: تا وقتی سالن خودش
            // merchant_id واقعی‌اش را ثبت نکند، PaymentService به‌صورت خودکار روی merchant_id
            // سراسری پلتفرم fallback می‌کند (به resolveMerchantId() در PaymentService نگاه کن).
            'zarinpal_merchant_id' => \App\Support\ZarinpalMerchant::RULES,
        ];
    }

    public function messages(): array
    {
        return $this->salonContactMessages() + \App\Services\Salon\SalonLogoService::MESSAGES + \App\Support\ZarinpalMerchant::MESSAGES;
    }
}
