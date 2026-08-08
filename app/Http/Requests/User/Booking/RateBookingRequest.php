<?php

namespace App\Http\Requests\User\Booking;

use Illuminate\Foundation\Http\FormRequest;

class RateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'rating' => 'required|integer|between:1,5',
            'review' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'لطفاً امتیاز نوبت را وارد کنید.',
            'rating.integer' => 'امتیاز باید عدد صحیح باشد.',
            'rating.between' => 'امتیاز باید بین ۱ تا ۵ باشد.',
            'review.max' => 'نظر شما نباید بیشتر از ۵۰۰ کاراکتر باشد.',
        ];
    }
}
