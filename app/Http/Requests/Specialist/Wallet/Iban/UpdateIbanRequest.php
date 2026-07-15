<?php

namespace App\Http\Requests\Specialist\Wallet\Iban;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIbanRequest extends FormRequest
{
    public function authorize(): bool
    {
// The actual authorization (wallet ownership) is checked in the controller with Policy(`updateIban`).
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'iban' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $digits = str_replace(' ', '', $value);
                    if (! preg_match('/^[0-9]{24}$/', $digits)) {
                        $fail('لطفاً ۲۴ رقم شماره شبا را بدون IR وارد کنید.');
                    }
                },
            ],
            'account_holder_name' => ['required', 'string', 'min:3', 'max:255'],
            'bank_name' => ['required', 'string'],
        ];
    }
}
