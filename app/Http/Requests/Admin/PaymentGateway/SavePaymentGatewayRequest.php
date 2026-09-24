<?php

namespace App\Http\Requests\Admin\PaymentGateway;

use App\Models\SalonPaymentGateway;
use App\Payments\GatewayCatalog;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ⭐ افزودن/ویرایش درگاه پرداخت سالن (مرحله‌ی ۱ چند درگاه، ۲۰۲۶-۰۹-۲۵). فیلدهای credentials از
 * App\Payments\GatewayCatalog می‌آن. موقع ویرایش، فیلدهای مخفی (رمز/کلید) اختیاری‌ان (خالی = بدون تغییر).
 */
class SavePaymentGatewayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // مسیر پشت middleware salon.owner است
    }

    private function editing(): ?SalonPaymentGateway
    {
        return $this->attributes->get('gateway');
    }

    private function driver(): string
    {
        return (string) ($this->editing()?->driver ?? $this->input('driver'));
    }

    protected function prepareForValidation(): void
    {
        $digits = fn ($v) => is_string($v) ? str_replace(
            ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '٫', ',', '٬', ' '],
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '.', '', '', ''],
            $v,
        ) : $v;

        $this->merge([
            'fee_percent' => $digits($this->input('fee_percent')) ?: 0,
            'fee_fixed_toman' => $digits($this->input('fee_fixed_toman')) ?: 0,
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        $rules = [
            'label' => ['nullable', 'string', 'max:60'],
            'is_active' => ['boolean'],
            'fee_percent' => ['numeric', 'min:0', 'max:'.SalonPaymentGateway::MAX_FEE_PERCENT],
            'fee_fixed_toman' => ['integer', 'min:0', 'max:'.SalonPaymentGateway::MAX_FEE_FIXED_TOMAN],
            'credentials' => ['array'],
            'clear' => ['nullable', 'array'],
        ];

        if (! $this->editing()) {
            $salonId = app(CurrentSalon::class)->get()?->id;
            $rules['driver'] = ['required', Rule::in(GatewayCatalog::keys()),
                Rule::unique('salon_payment_gateways', 'driver')->where('salon_id', $salonId)];
        }

        foreach (GatewayCatalog::fields($this->driver()) as $name => $field) {
            $optional = ! empty($field['optional']) || ($this->editing() && $field['secret']);
            $required = $optional ? 'nullable' : 'required';
            $rules["credentials.$name"] = [$required, ...$field['rules']];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'driver.required' => 'نوع درگاه را انتخاب کنید.',
            'driver.in' => 'این درگاه پشتیبانی نمی‌شود.',
            'driver.unique' => 'این درگاه قبلاً برای سالن اضافه شده است؛ همان را ویرایش کنید.',
            'credentials.*.required' => 'همه‌ی اطلاعات اتصال درگاه را وارد کنید.',
            'credentials.merchant_id.regex' => 'کد پذیرنده‌ی زرین‌پال باید ۳۶ کاراکتر به شکل xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx باشد.',
            'credentials.merchant_config_id.digits_between' => 'شناسه‌ی پیکربندی آسان پرداخت باید عدد باشد.',
            'fee_percent.max' => 'کارمزد درصدی حداکثر '.SalonPaymentGateway::MAX_FEE_PERCENT.' درصد است.',
            'fee_percent.numeric' => 'کارمزد درصدی باید عدد باشد.',
            'fee_fixed_toman.max' => 'کارمزد ثابت حداکثر '.number_format(SalonPaymentGateway::MAX_FEE_FIXED_TOMAN).' تومان است.',
            'fee_fixed_toman.integer' => 'کارمزد ثابت باید عدد صحیح (تومان) باشد.',
        ];
    }

    public function attributes(): array
    {
        return ['label' => 'نام نمایشی'];
    }
}
