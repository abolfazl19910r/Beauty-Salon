<?php

namespace App\Http\Requests\Security;

use Illuminate\Foundation\Http\FormRequest;

class CheckPasswordStrengthRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'رمز عبور الزامی است.',
            'password.min'      => 'رمز عبور باید حداقل ۸ کاراکتر باشد.',
        ];
    }
}
