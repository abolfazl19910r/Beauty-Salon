<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class NewPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required'    => 'توکن بازیابی الزامی است.',
            'email.required'    => 'ایمیل الزامی است.',
            'email.email'       => 'فرمت ایمیل معتبر نیست.',
            'password.required' => 'رمز عبور جدید الزامی است.',
            'password.confirmed'=> 'تکرار رمز عبور مطابقت ندارد.',
        ];
    }
}
