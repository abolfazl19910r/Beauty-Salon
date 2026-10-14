<?php

namespace App\Http\Requests\SuperAdmin;

use App\Models\Plan;
use App\Support\Features\Feature;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** ساخت/ویرایش پلن در پنل سوپرادمین (بسته‌ی پلن‌ها، ۲۰۲۶-۱۰-۱۴). قیمت خالی = این بازه فروخته نمی‌شود. */
class SavePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasRole('super-admin');
    }

    protected function prepareForValidation(): void
    {
        // ارقام فارسی و جداکننده‌ی هزارگان در فیلدهای عددی
        $numeric = ['price_1m', 'price_3m', 'price_6m', 'price_12m', 'included_specialists', 'extra_specialist_price_per_month', 'sms_quota_per_month', 'trial_days', 'sort'];
        $clean = [];
        foreach ($numeric as $field) {
            $value = $this->input($field);
            if (is_string($value)) {
                $value = strtr($value, ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9', ',' => '', '٬' => '', ' ' => '']);
                $clean[$field] = $value === '' ? null : $value;
            }
        }
        $this->merge($clean + [
            'is_public' => $this->boolean('is_public'),
            'is_active' => $this->boolean('is_active'),
            'features' => array_values((array) $this->input('features', [])),
        ]);
    }

    public function rules(): array
    {
        /** @var Plan|null $plan */
        $plan = $this->route('plan');
        $isTrial = (bool) $plan?->is_trial;
        $price = $isTrial ? ['nullable', 'prohibited'] : ['nullable', 'integer', 'min:0', 'max:9999999999'];

        return [
            'key' => ['required', 'string', 'max:40', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique('plans', 'key')->ignore($plan?->id)],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_public' => ['boolean'],
            'is_active' => ['boolean'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:65000'],
            'price_1m' => $price,
            'price_3m' => $price,
            'price_6m' => $price,
            'price_12m' => $price,
            'included_specialists' => ['required', 'integer', 'min:1', 'max:1000'],
            'extra_specialist_price_per_month' => ['required', 'integer', 'min:0', 'max:999999999'],
            'sms_quota_per_month' => ['required', 'integer', 'min:0', 'max:1000000'],
            'trial_days' => $isTrial ? ['required', 'integer', 'min:0', 'max:365'] : ['nullable', 'prohibited'],
            'features' => ['array'],
            'features.*' => ['string', Rule::in(Feature::keys())],
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            $plan = $this->route('plan');
            if ($plan?->is_trial) {
                return;
            }
            $prices = array_filter([$this->input('price_1m'), $this->input('price_3m'), $this->input('price_6m'), $this->input('price_12m')], fn ($p) => $p !== null);
            if ($prices === []) {
                $validator->errors()->add('price_1m', 'دست‌کم قیمت یک بازه لازم است.');
            }
        }];
    }

    public function attributes(): array
    {
        return [
            'key' => 'شناسه', 'name' => 'نام', 'price_1m' => 'قیمت یک‌ماهه', 'price_3m' => 'قیمت سه‌ماهه', 'price_6m' => 'قیمت شش‌ماهه',
            'price_12m' => 'قیمت دوازده‌ماهه', 'included_specialists' => 'متخصص شامل', 'extra_specialist_price_per_month' => 'قیمت متخصص اضافه',
            'sms_quota_per_month' => 'سهمیه‌ی پیامک', 'trial_days' => 'روزهای آزمایشی',
        ];
    }

    public function messages(): array
    {
        return [
            'key.regex' => 'شناسه فقط حروف کوچک انگلیسی، رقم و _ (با حرف شروع شود).',
            'price_1m.prohibited' => 'پلن آزمایشی قیمت ندارد.',
            'trial_days.prohibited' => 'روزهای آزمایشی فقط برای پلن آزمایشی است.',
        ];
    }

    /** @return array<string, mixed> */
    public function planAttributes(): array
    {
        $data = $this->safe()->except(['features']);
        $data['sort'] ??= 0;

        return $data;
    }
}
