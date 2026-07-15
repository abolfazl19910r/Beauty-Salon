<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class ApplyDiscountToBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'code'       => ['required', 'string'],
            'service_id' => ['required', 'exists:beauty_services,id'],
        ];
    }
}
