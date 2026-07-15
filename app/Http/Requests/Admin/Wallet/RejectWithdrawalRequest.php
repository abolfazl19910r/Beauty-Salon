<?php

namespace App\Http\Requests\Admin\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class RejectWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('access_admin_panel');
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
