<?php

namespace App\Http\Requests\Specialist;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIbanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('specialist');
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
            'account_holder_name' => 'required|string|min:3|max:255',
            'bank_name'           => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'iban.required'                => 'شماره شبا الزامی است.',
            'account_holder_name.required' => 'نام صاحب حساب الزامی است.',
            'account_holder_name.min'      => 'نام صاحب حساب باید حداقل ۳ کاراکتر باشد.',
            'bank_name.required'           => 'نام بانک الزامی است.',
        ];
    }
}
