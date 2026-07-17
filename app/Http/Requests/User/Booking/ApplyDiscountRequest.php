<?php

namespace App\Http\Requests\User\Booking;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Previously, BookingDiscountController imported the ApplyDiscountRequest class from the
 * App\Http\Requests\Booking\ namespace (which no longer exists). This file has been replaced by its
 * neighbor, CheckDiscountRequest , in the correct namespace.
 */
class ApplyDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50'],
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
