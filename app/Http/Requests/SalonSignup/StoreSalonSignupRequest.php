<?php

namespace App\Http\Requests\SalonSignup;

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
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:100', 'alpha_dash',
                Rule::unique('salons', 'slug'),
            ],
            'subscription_type' => ['required', 'in:1m,3m,6m,12m'],
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

    public function messages(): array
    {
        return [
            'slug.unique' => 'این آدرس قبلاً برای سالن دیگری استفاده شده است.',
            'slug.alpha_dash' => 'آدرس فقط می‌تواند شامل حروف انگلیسی، عدد، خط تیره و زیرخط باشد.',
            'owner_phone.regex' => 'شماره موبایل باید با ۰۹ شروع شود و ۱۱ رقم باشد.',
            'owner_phone.unique' => 'ادمینی با این شماره موبایل از قبل ثبت‌نام کرده است.',
        ];
    }
}
