<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRescheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'booking_time' => 'required|date|after:now',
        ];
    }

    public function messages(): array
    {
        return [
            'booking_time.required' => 'لطفاً زمان جدید نوبت را انتخاب کنید.',
            'booking_time.date'     => 'فرمت تاریخ معتبر نیست.',
            'booking_time.after'    => 'زمان جدید باید بعد از لحظه‌ی فعلی باشد.',
        ];
    }
}
