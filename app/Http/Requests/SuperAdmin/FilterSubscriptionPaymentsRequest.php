<?php

namespace App\Http\Requests\SuperAdmin;

use App\Services\SuperAdmin\SubscriptionPaymentReport;
use App\Support\JalaliDateInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterSubscriptionPaymentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // مسیر پشت middleware super_admin است
    }

    public function rules(): array
    {
        $jalali = fn (string $attr, $value, $fail) => JalaliDateInput::isValid($value) ?: $fail('تاریخ را به شکل ۱۴۰۵/۰۷/۰۱ وارد کنید.');

        return [
            'q' => ['nullable', 'string', 'max:100'],
            'salon_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in(['pending', 'paid', 'failed'])],
            'payment_method' => ['nullable', Rule::in(['online', 'manual'])],
            'subscription_type' => ['nullable', Rule::in(['1m', '3m', '6m', '12m'])],
            'date_field' => ['nullable', Rule::in(['created_at', 'paid_at'])],
            'date_from' => ['nullable', 'string', 'max:20', $jalali],
            'date_to' => ['nullable', 'string', 'max:20', $jalali],
            'amount_min' => ['nullable', 'integer', 'min:0'],
            'amount_max' => ['nullable', 'integer', 'min:0'],
            'sort' => ['nullable', Rule::in(array_keys(SubscriptionPaymentReport::SORTS))],
        ];
    }

    public function filters(): array
    {
        return array_filter($this->validated(), fn ($v) => $v !== null && $v !== '');
    }
}
