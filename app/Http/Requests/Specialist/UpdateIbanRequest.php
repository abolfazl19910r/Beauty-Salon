<?php

namespace App\Http\Requests\Specialist;

use App\Rules\ValidIban;
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
            'iban' => ['required', 'string', new ValidIban],
            'account_holder_name' => ['required', 'string', 'min:3', 'max:255'],
            'bank_name' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'iban.required' => 'شماره شبا الزامی است.',
            'account_holder_name.required' => 'نام صاحب حساب الزامی است.',
            'account_holder_name.min' => 'نام صاحب حساب باید حداقل ۳ کاراکتر باشد.',
            'bank_name.required' => 'نام بانک الزامی است.',
        ];
    }
}
