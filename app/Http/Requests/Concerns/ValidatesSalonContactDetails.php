<?php

namespace App\Http\Requests\Concerns;

use App\Support\SalonWorkingHours;
use Illuminate\Validation\Validator;

/**
 * ⭐ اطلاعات تماس و فعالیت سالن (۲۰۲۶-۰۹-۲۳) — آدرس، تلفن سالن، سابقه‌ی کاری، ساعات کاری.
 * مشترک بین فرم ثبت‌نام عمومی (همه اجباری) و فرم‌های ساخت/ویرایش سوپرادمین (همه اختیاری، چون
 * سالن‌های موجود هنوز این‌ها رو ندارن و تست‌ها/فرم‌های قدیمی بدون این فیلدها پست می‌کنن).
 */
trait ValidatesSalonContactDetails
{
    protected function salonContactRules(bool $required): array
    {
        $presence = $required ? 'required' : 'nullable';

        return [
            'salon_address' => [$presence, 'string', 'min:10', 'max:500'],
            // ثابت یا موبایل، ۱۱ رقم با صفر اول (مثلاً 02112345678 یا 09121234567)؛ ارقام فارسی و
            // خط‌تیره/فاصله قبلش در prepareSalonContactInput() حذف/تبدیل شدن.
            'salon_phone' => [$presence, 'string', 'regex:/^0[0-9]{10}$/'],
            'experience_years' => [$presence, 'integer', 'min:0', 'max:80'],
            'working_hours' => [$presence, 'array'],
        ];
    }

    protected function salonContactMessages(): array
    {
        return [
            'salon_address.required' => 'آدرس سالن را وارد کنید.',
            'salon_address.min' => 'آدرس سالن را کامل‌تر وارد کنید.',
            'salon_phone.required' => 'شماره تماس سالن را وارد کنید.',
            'salon_phone.regex' => 'شماره تماس سالن باید ۱۱ رقم و با ۰ شروع شود (مثلاً ۰۲۱۱۲۳۴۵۶۷۸).',
            'experience_years.required' => 'سابقه‌ی کاری سالن را وارد کنید.',
            'experience_years.integer' => 'سابقه‌ی کاری باید یک عدد باشد.',
            'experience_years.max' => 'سابقه‌ی کاری حداکثر ۸۰ سال است.',
            'working_hours.required' => 'ساعات کاری سالن را وارد کنید.',
        ];
    }

    protected function prepareSalonContactInput(): void
    {
        $toLatin = fn ($value) => is_string($value)
            ? strtr($value, [
                '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
                '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            ])
            : $value;

        $merge = [];

        if ($this->has('salon_phone')) {
            $merge['salon_phone'] = preg_replace('/[\s\-()]/', '', (string) $toLatin($this->input('salon_phone')));
        }

        if ($this->has('experience_years')) {
            $merge['experience_years'] = trim((string) $toLatin($this->input('experience_years')));
        }

        if ($this->has('salon_address')) {
            $merge['salon_address'] = trim((string) $this->input('salon_address'));
        }

        foreach ($merge as $key => $value) {
            if ($value === '') {
                $merge[$key] = null;
            }
        }

        $this->merge($merge);
    }

    protected function validateSalonWorkingHours(Validator $validator): void
    {
        if (! $this->filled('working_hours')) {
            return; // presence خودش در rules چک شده (اجباری فقط در ثبت‌نام عمومی)
        }

        foreach (SalonWorkingHours::errors($this->input('working_hours')) as $field => $message) {
            $validator->errors()->add($field, $message);
        }
    }

    /** ورودی معتبرشده → ستون‌های salons (فقط کلیدهایی که واقعاً فرستاده شدن). */
    public function salonContactAttributes(): array
    {
        $attributes = [];

        if ($this->exists('salon_address')) {
            $attributes['address'] = $this->input('salon_address');
        }

        if ($this->exists('salon_phone')) {
            $attributes['phone'] = $this->input('salon_phone');
        }

        if ($this->exists('experience_years')) {
            $years = $this->input('experience_years');
            $attributes['established_year'] = $years === null
                ? null
                : \App\Models\Salon::establishedYearFromExperience((int) $years);
        }

        if ($this->filled('working_hours')) {
            $attributes['working_hours'] = SalonWorkingHours::fromInput($this->input('working_hours'));
        }

        return $attributes;
    }
}
