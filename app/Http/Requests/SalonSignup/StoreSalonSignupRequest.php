<?php

namespace App\Http\Requests\SalonSignup;

use App\Http\Requests\Concerns\ValidatesSalonContactDetails;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

/**
 * ⭐ فاز ۲ SaaS، محور «۴. ثبت‌نام عمومی سالن (self-service)». قوانین عمداً تقریباً کپی از
 * App\Http\Requests\SuperAdmin\StoreSalonRequest است — همون شکل slug/phone — با دو فرق: اینجا
 * public است (authorize() همیشه true، برخلاف چک hasRole('super-admin') آنجا، چون اصلاً هیچ‌کس
 * لاگین نیست) و admin_password_confirmation عمداً وجود نداره چون این متدها هیچ‌وقت مستقیم روی
 * FormRequest صدا زده نمی‌شن؛ Rules\Password::defaults() (نه صرفاً min:8) استفاده شده چون این
 * مسیر عمومی روی اینترنته، نه یک فرم داخلی که فقط سوپرادمین بهش دسترسی داره.
 */
class StoreSalonSignupRequest extends FormRequest
{
    use ValidatesSalonContactDetails;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareSalonContactInput();
    }

    public function rules(): array
    {
        return $this->salonContactRules(required: true) + [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:100', 'alpha_dash',
                Rule::unique('salons', 'slug'),
            ],
            // ⭐ دیگه در فرم نمایش داده نمی‌شه (۲۰۲۶-۰۹-۲۳) — فقط hidden، از ?plan= صفحه‌ی فروش؛
            // خالی/نامعتبر نباید ثبت‌نام رو رد کنه، پس nullable و در سرویس پیش‌فرض ۱m.
            'subscription_type' => ['nullable', 'in:1m,3m,6m,12m'],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_phone' => [
                'required', 'string', 'regex:/^09[0-9]{9}$/',
                // ⭐ همون قانون StoreSalonRequest سوپرادمین: چون هنوز هیچ سالنی وجود نداره که
                // phone رو بهش scope کنیم، سراسری بین user_type='staff' چک می‌شه.
                Rule::unique('users', 'phone')->where(fn ($query) => $query->where('user_type', 'staff')),
            ],
            'owner_password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];
    }

    /**
     * ⭐ ۲۰۲۶-۰۹-۲۳: آدرس/تلفن/سابقه/ساعات کاری سالن همون لحظه‌ی ساخت گرفته می‌شن تا سایت
     * سالن از روز اول اطلاعات واقعی خودش رو نشون بده (به ValidatesSalonContactDetails نگاه کن).
     */
    public function after(): array
    {
        return [fn ($validator) => $this->validateSalonWorkingHours($validator)];
    }

    public function messages(): array
    {
        return $this->salonContactMessages() + [
            'slug.unique' => 'این آدرس قبلاً برای سالن دیگری استفاده شده است.',
            'slug.alpha_dash' => 'آدرس فقط می‌تواند شامل حروف انگلیسی، عدد، خط تیره و زیرخط باشد.',
            'owner_phone.regex' => 'شماره موبایل باید با ۰۹ شروع شود و ۱۱ رقم باشد.',
            'owner_phone.unique' => 'ادمینی با این شماره موبایل از قبل ثبت‌نام کرده است.',
        ];
    }
}
