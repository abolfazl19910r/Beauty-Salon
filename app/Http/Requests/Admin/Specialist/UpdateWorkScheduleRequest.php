<?php

namespace App\Http\Requests\Admin\Specialist;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('access_admin_panel');
    }

    public function rules(): array
    {
        return [
            'work_days'   => ['required', 'array', 'min:1'],
            'work_days.*' => ['integer', 'between:0,6'],
            'start_time'  => ['required', 'date_format:H:i'],
            'end_time'    => ['required', 'date_format:H:i', 'after:start_time'],
            'is_active'   => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'work_days.required'  => 'حداقل یک روز کاری باید انتخاب شود.',
            'work_days.min'       => 'حداقل یک روز کاری باید انتخاب شود.',
            'work_days.*.between' => 'روز هفته نامعتبر است.',
            'start_time.required' => 'ساعت شروع الزامی است.',
            'end_time.required'   => 'ساعت پایان الزامی است.',
            'end_time.after'      => 'ساعت پایان باید بعد از ساعت شروع باشد.',
        ];
    }
}
