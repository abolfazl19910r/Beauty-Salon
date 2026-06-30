<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class CheckDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'code'       => 'required|string|max:50',
            'service_id' => 'required|exists:beauty_services,id',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required'       => 'لطفاً کد تخفیف را وارد کنید.',
            'code.max'            => 'کد تخفیف نمی‌تواند بیشتر از ۵۰ کاراکتر باشد.',
            'service_id.required' => 'شناسه‌ی سرویس الزامی است.',
            'service_id.exists'   => 'سرویس انتخابی معتبر نیست.',
        ];
    }
}
