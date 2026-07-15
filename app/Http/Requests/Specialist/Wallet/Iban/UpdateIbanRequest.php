<?php

namespace App\Http\Requests\Specialist\Wallet\Iban;

use App\Rules\ValidIban;
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
            'iban' => ['required', 'string', new ValidIban],
            'account_holder_name' => ['required', 'string', 'min:3', 'max:255'],
            'bank_name' => ['required', 'string'],
        ];
    }
}
