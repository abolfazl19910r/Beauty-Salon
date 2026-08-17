<?php

namespace App\Http\Requests\Specialist;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        // ⭐ Fix (test-writing session 6): real authorization happens in the controller
        // via SpecialistPolicy::manageLeaves — see that class for why hasRole('specialist')
        // was removed there.
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'start_date_jalali' => 'required|string',
            'end_date_jalali' => 'required|string',
            'reason' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'start_date_jalali.required' => 'تاریخ شروع مرخصی الزامی است.',
            'end_date_jalali.required' => 'تاریخ پایان مرخصی الزامی است.',
            'reason.max' => 'دلیل مرخصی نباید بیشتر از ۲۵۵ کاراکتر باشد.',
        ];
    }
}
