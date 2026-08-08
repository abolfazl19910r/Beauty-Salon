<?php

namespace App\Http\Requests\Admin\Leave;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeaveStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('access_admin_panel');
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:approved,rejected'],
            'reject_reason' => ['nullable', 'required_if:status,rejected', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'وضعیت الزامی است.',
            'status.in' => 'وضعیت نامعتبر است.',
            'reject_reason.required_if' => 'برای رد درخواست، ذکر دلیل الزامی است.',
        ];
    }
}
