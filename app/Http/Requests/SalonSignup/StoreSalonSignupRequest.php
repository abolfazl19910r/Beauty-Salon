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
        if ($this->has('slug')) {
            $this->merge(['slug' => \App\Support\SalonSubdomain::normalize($this->input('slug'))]);
        }
    }

    public function rules(): array
    {
        return $this->salonContactRules(required: true) + [
            // ⭐ لوگوی اختصاصی سالن (۲۰۲۶-۰۹-۲۴) — اختیاری.
            'logo' => \App\Services\Salon\SalonLogoService::RULES,
            // ⭐ کد پذیرنده‌ی زرین‌پال سالن (۲۰۲۶-۰۹-۲۴) — اختیاری در ثبت‌نام، ولی بدونش هیچ پرداخت آنلاینی ممکن نیست.
            'zarinpal_merchant_id' => \App\Support\ZarinpalMerchant::RULES,
            'name' => ['required', 'string', 'max:255'],
            'slug' => \App\Support\SalonSubdomain::rules(),
            // ⭐ دیگه در فرم نمایش داده نمی‌شه (۲۰۲۶-۰۹-۲۳) — فقط hidden، از ?plan= صفحه‌ی فروش؛
            // خالی/نامعتبر نباید ثبت‌نام رو رد کنه، پس nullable و در سرویس پیش‌فرض ۱m.
            'subscription_type' => ['nullable', 'in:1m,3m,6m,12m'],
            // «خرید مستقیم» (buy) یا مسیر عادی آزمایشی (trial) — فقط مسیر بعد از تایید OTP رو عوض می‌کنه.
            'intent' => ['nullable', 'in:trial,buy'],
            // تعداد متخصص سالن = سقف متخصص؛ بیشتر از تعداد شامل‌شده در پلن هزینه‌ی ماهانه دارد (SubscriptionPricing).
            'specialists_count' => ['required', 'integer', 'min:1', 'max:'.max(1, (int) config('billing.max_signup_specialists', 50))],
            // ⭐ تصمیم ۵ (۲۰۲۶-۱۰-۲۲): مدیر/منشیِ واردشده سالن تازه را برای حساب خودش می‌سازد؛ مشخصات مدیر لازم نیست
            'owner_name' => [$this->existingOwner() ? 'nullable' : 'required', 'string', 'max:255'],
            'owner_phone' => [
                $this->existingOwner() ? 'nullable' : 'required', 'string', 'regex:/^09[0-9]{9}$/',
                // ⭐ همون قانون StoreSalonRequest سوپرادمین: چون هنوز هیچ سالنی وجود نداره که
                // phone رو بهش scope کنیم، سراسری بین user_type='staff' چک می‌شه.
                Rule::unique('users', 'phone')->where(fn ($query) => $query->where('user_type', 'staff')),
            ],
            'owner_password' => [$this->existingOwner() ? 'nullable' : 'required', 'confirmed', Rules\Password::defaults()],
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

    /** حساب کادرِ واردشده‌ای که دست‌کم عضو یک سالن است (سالن دوم به همان حساب) */
    public function existingOwner(): ?\App\Models\User
    {
        return \App\Http\Controllers\SalonSignup\SalonSignupController::existingOwner();
    }

    public function messages(): array
    {
        return $this->salonContactMessages() + \App\Services\Salon\SalonLogoService::MESSAGES + \App\Support\ZarinpalMerchant::MESSAGES + \App\Support\SalonSubdomain::messages() + [
            'specialists_count.required' => 'تعداد متخصص‌های سالن را وارد کنید.',
            'specialists_count.min' => 'سالن حداقل یک متخصص دارد.',
            'specialists_count.max' => 'برای بیشتر از :max متخصص با پشتیبانی تماس بگیرید.',
            'owner_phone.regex' => 'شماره موبایل باید با ۰۹ شروع شود و ۱۱ رقم باشد.',
            'owner_phone.unique' => 'این شماره حساب ماهرو دارد (مدیر یا منشی سالن دیگر). برای ساختن سالن دوم، اول با همین شماره وارد شوید و دوباره این صفحه را باز کنید.',
        ];
    }
}
