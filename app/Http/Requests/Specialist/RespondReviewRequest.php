<?php

namespace App\Http\Requests\Specialist;

use Illuminate\Foundation\Http\FormRequest;

class RespondReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('specialist');
    }

    public function rules(): array
    {
        return [
            'response' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'response.required' => 'لطفاً پاسخ خود را وارد کنید.',
            'response.max' => 'پاسخ شما نباید بیشتر از ۱۰۰۰ کاراکتر باشد.',
        ];
    }
}
