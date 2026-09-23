<?php

namespace App\Http\Requests\SuperAdmin;

use App\Services\SuperAdmin\SalonListFilter;
use App\Support\JalaliDateInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterSalonsRequest extends FormRequest
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
            'status' => ['nullable', Rule::in(array_keys(SalonListFilter::STATUSES))],
            'subscription_type' => ['nullable', Rule::in(['1m', '3m', '6m', '12m'])],
            'quota' => ['nullable', Rule::in(['full', 'available'])],
            'started_from' => ['nullable', 'string', 'max:20', $jalali],
            'started_to' => ['nullable', 'string', 'max:20', $jalali],
            'ends_from' => ['nullable', 'string', 'max:20', $jalali],
            'ends_to' => ['nullable', 'string', 'max:20', $jalali],
            'sort' => ['nullable', Rule::in(array_keys(SalonListFilter::SORTS))],
        ];
    }

    public function filters(): array
    {
        return array_filter($this->validated(), fn ($v) => $v !== null && $v !== '');
    }
}
