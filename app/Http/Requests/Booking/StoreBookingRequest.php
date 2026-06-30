<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'service_id'    => 'required|exists:beauty_services,id',
            'specialist_id' => 'required|exists:specialists,id',
            'booking_time'  => 'required|date|after:now',
            'discount_code' => 'nullable|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'service_id.required'    => 'لطفاً سرویس مورد نظر را انتخاب کنید.',
            'service_id.exists'      => 'سرویس انتخابی معتبر نیست.',
            'specialist_id.required' => 'لطفاً متخصص مورد نظر را انتخاب کنید.',
            'specialist_id.exists'   => 'متخصص انتخابی معتبر نیست.',
            'booking_time.required'  => 'لطفاً زمان نوبت را انتخاب کنید.',
            'booking_time.date'      => 'فرمت تاریخ نوبت معتبر نیست.',
            'booking_time.after'     => 'زمان نوبت باید بعد از لحظه‌ی فعلی باشد.',
            'discount_code.max'      => 'کد تخفیف نمی‌تواند بیشتر از ۵۰ کاراکتر باشد.',
        ];
    }
}
