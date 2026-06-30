<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class ApplyDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'لطفاً کد تخفیف را وارد کنید.',
            'code.max'      => 'کد تخفیف نمی‌تواند بیشتر از ۵۰ کاراکتر باشد.',
        ];
    }
}
