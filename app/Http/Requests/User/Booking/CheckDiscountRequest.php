<?php

namespace App\Http\Requests\User\Booking;

use Illuminate\Foundation\Http\FormRequest;

class CheckDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Previously, service_id was incorrectly required when it wasn't used in the calculation at all
     * and the checkout page wouldn't submit it — result: permanent 422
     * error on discount preview. Both fields are now optional; booking_id has also been added
     * so that BookingDiscountController::resolveBaseAmount() can use the actual
     * advance payment amount of the same booking as the basis for the calculation.
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50'],
            'service_id' => ['nullable', 'exists:beauty_services,id'],
            'booking_id' => ['nullable', 'exists:bookings,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'لطفاً کد تخفیف را وارد کنید.',
            'code.max' => 'کد تخفیف نمی‌تواند بیشتر از ۵۰ کاراکتر باشد.',
            'service_id.exists' => 'سرویس انتخابی معتبر نیست.',
            'booking_id.exists' => 'نوبت انتخابی معتبر نیست.',
        ];
    }
}
