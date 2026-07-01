<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'phone'    => ['required', 'string', 'regex:/^09[0-9]{9}$/', 'unique:users,phone'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'     => 'نام الزامی است.',
            'name.max'          => 'نام نباید بیشتر از ۲۵۵ کاراکتر باشد.',
            'phone.required'    => 'شماره موبایل الزامی است.',
            'phone.regex'       => 'شماره موبایل باید با ۰۹ شروع شود و ۱۱ رقم باشد.',
            'phone.unique'      => 'این شماره موبایل قبلاً ثبت شده است.',
            'password.required' => 'رمز عبور الزامی است.',
            'password.confirmed'=> 'تکرار رمز عبور مطابقت ندارد.',
        ];
    }
}
