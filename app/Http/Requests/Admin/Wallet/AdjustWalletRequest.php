<?php

namespace App\Http\Requests\Admin\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class AdjustWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('access_admin_panel');
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric'],
            'description' => ['required', 'string', 'max:500'],
        ];
    }
}
