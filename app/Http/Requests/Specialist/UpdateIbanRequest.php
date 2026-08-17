<?php

namespace App\Http\Requests\Specialist;

use App\Rules\ValidIban;
use Illuminate\Foundation\Http\FormRequest;

class UpdateIbanRequest extends FormRequest
{
    public function authorize(): bool
    {
        // ⭐ Fix (test-writing session 6): the real authorization (wallet ownership) is
        // checked in the controller via the SpecialistWalletPolicy::updateIban ability.
        // This used to require hasRole('specialist'), which nothing in the production
        // registration/specialist-creation flow ever assigns — that made this endpoint
        // permanently return 403 for every specialist. Same root cause and same fix
        // pattern already applied to the withdrawal Form Request.
        return auth()->check();
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
