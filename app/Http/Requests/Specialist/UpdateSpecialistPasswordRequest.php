<?php

namespace App\Http\Requests\Specialist;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateSpecialistPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required'       => 'رمز عبور فعلی الزامی است.',
            'current_password.current_password' => 'رمز عبور فعلی اشتباه است.',
            'password.required'               => 'رمز عبور جدید الزامی است.',
            'password.confirmed'              => 'تکرار رمز عبور جدید مطابقت ندارد.',
        ];
    }
}
