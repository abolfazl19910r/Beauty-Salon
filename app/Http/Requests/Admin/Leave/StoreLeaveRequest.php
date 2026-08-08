<?php

namespace App\Http\Requests\Admin\Leave;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('access_admin_panel');
    }

    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date_format:Y-m-d', 'after:yesterday'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.required' => 'تاریخ شروع مرخصی الزامی است.',
            'end_date.required' => 'تاریخ پایان مرخصی الزامی است.',
            'end_date.after_or_equal' => 'تاریخ پایان باید بعد یا مساوی تاریخ شروع باشد.',
            'reason.max' => 'دلیل مرخصی نباید بیشتر از ۲۵۵ کاراکتر باشد.',
        ];
    }
}
