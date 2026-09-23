<?php

namespace App\Http\Requests\SuperAdmin;

use App\Http\Requests\Concerns\ValidatesSalonContactDetails;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSalonRequest extends FormRequest
{
    use ValidatesSalonContactDetails;

    public function authorize(): bool
    {
        // ⭐ Redundant with the 'super_admin' route middleware (EnsureSuperAdmin) by design —
        // defense in depth, same pattern as every other admin-facing FormRequest in this project.
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
            'name' => ['required', 'string', 'max:255'],
            // ⭐ پیگیری «محور ۳» (۲۰۲۶-۰۹-۲۰) — اختیاری همینجا هم؛ می‌تونه بعداً از صفحه‌ی ویرایش
            // هم پر/عوض بشه، دقیقاً مثل zarinpal_merchant_id.
            'tagline' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'slug' => [
                'required', 'string', 'max:100', 'alpha_dash',
                Rule::unique('salons', 'slug'),
            ],
            'subscription_type' => ['required', 'in:1m,3m,6m,12m'],
            'max_specialists_count' => ['required', 'integer', 'min:0'],
            'module_permissions' => ['nullable', 'array'],
            'module_permissions.*' => ['string'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_phone' => [
                'required', 'string', 'regex:/^09[0-9]{9}$/',
                // ⭐ Customer identity redesign: staff phones stay globally unique — scoped to
                // user_type='staff' so this doesn't collide with an unrelated customer of some
                // OTHER salon who happens to share this number, which is allowed under that design.
                Rule::unique('users', 'phone')->where(fn ($query) => $query->where('user_type', 'staff')),
            ],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return $this->salonContactMessages() + [
            'slug.unique' => 'این آدرس قبلاً برای سالن دیگری استفاده شده است.',
            'slug.alpha_dash' => 'آدرس فقط می‌تواند شامل حروف انگلیسی، عدد، خط تیره و زیرخط باشد.',
            'admin_phone.regex' => 'شماره موبایل باید با ۰۹ شروع شود و ۱۱ رقم باشد.',
            'admin_phone.unique' => 'ادمینی با این شماره موبایل از قبل وجود دارد.',
        ];
    }
}
