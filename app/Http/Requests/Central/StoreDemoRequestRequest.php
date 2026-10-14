<?php

namespace App\Http\Requests\Central;

use App\Models\DemoRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * فرم «درخواست دموی رایگان» صفحه‌ی فروش. شماره با ارقام فارسی/عربی و ‎+98 پذیرفته و به 09xxxxxxxxx یکسان می‌شود.
 * فیلد website تله‌ی ربات است (در صفحه پنهان)؛ پرشدنش خطای اعتبارسنجی نمی‌دهد — کنترلر بی‌صدا ردش می‌کند.
 */
class StoreDemoRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->name) ? trim($this->name) : $this->name,
            'salon' => is_string($this->salon) ? trim($this->salon) : $this->salon,
            'phone' => self::normalizePhone((string) $this->phone),
        ]);
    }

    public static function normalizePhone(string $raw): string
    {
        $digits = strtr($raw, ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']);
        $digits = preg_replace('/\D/', '', $digits) ?? '';
        if (str_starts_with($digits, '98') && strlen($digits) === 12) {
            $digits = '0'.substr($digits, 2);
        }
        if (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            $digits = '0'.$digits;
        }

        return $digits;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'salon' => ['nullable', 'string', 'max:150'],
            'phone' => ['required', 'regex:/^09[0-9]{9}$/'],
            'type' => ['nullable', Rule::in(array_keys(DemoRequest::TYPES))],
            'website' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'لطفاً نام خود را وارد کنید.',
            'name.min' => 'لطفاً نام خود را وارد کنید.',
            'phone.required' => 'شماره موبایل را وارد کنید.',
            'phone.regex' => 'شماره موبایل معتبر نیست (مثلاً ۰۹۱۲۳۴۵۶۷۸۹).',
            'type.in' => 'نوع سالن را از فهرست انتخاب کنید.',
        ];
    }
}
