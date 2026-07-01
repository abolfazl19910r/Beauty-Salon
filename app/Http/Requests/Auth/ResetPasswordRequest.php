<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token'    => ['required'],
            'code'     => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required'    => 'توکن بازیابی الزامی است.',
            'code.required'     => 'کد تأیید الزامی است.',
            'code.size'         => 'کد تأیید باید ۶ رقم باشد.',
            'password.required' => 'رمز عبور جدید الزامی است.',
            'password.min'      => 'رمز عبور باید حداقل ۸ کاراکتر باشد.',
            'password.confirmed'=> 'تکرار رمز عبور مطابقت ندارد.',
        ];
    }
}
