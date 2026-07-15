<?php

namespace App\Http\Requests\Admin\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class ApproveWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('access_admin_panel');
    }

    public function rules(): array
    {
        return [
            'admin_note' => ['nullable', 'string', 'max:500'],
            'payment_reference' => ['required', 'string', 'max:255'],
        ];
    }
}
