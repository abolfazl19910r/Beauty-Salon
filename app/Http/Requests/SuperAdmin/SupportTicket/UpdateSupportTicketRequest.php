<?php

namespace App\Http\Requests\SuperAdmin\SupportTicket;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super-admin');
    }

    public function rules(): array
    {
        return [
            'priority' => ['sometimes', 'in:low,medium,high,urgent'],
            'assigned_to' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'priority.in' => 'اولویت انتخاب‌شده معتبر نیست.',
            'assigned_to.exists' => 'کاربر انتخاب‌شده برای واگذاری معتبر نیست.',
        ];
    }
}
