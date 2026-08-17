<?php

namespace App\Http\Requests\Specialist;

use Illuminate\Foundation\Http\FormRequest;

class RespondReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        // ⭐ Fix (test-writing session 6): consistent with the same fix applied elsewhere
        // in this class group. Note: this Form Request is currently dead code —
        // SpecialistReviewController::respond() validates inline with a plain Request
        // instead of using this class — so this had no live effect, but is corrected
        // for consistency in case it's wired up later.
        return auth()->check();
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
