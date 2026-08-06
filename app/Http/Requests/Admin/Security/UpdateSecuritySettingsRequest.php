<?php

namespace App\Http\Requests\Admin\Security;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSecuritySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('access_admin_panel');
    }

    public function rules(): array
    {
        return [
            'password_expiry_days' => ['required', 'integer', 'min:30', 'max:365'],
        ];
    }

    public function messages(): array
    {
        return [
            'password_expiry_days.required' => 'مهلت تازگی رمز عبور الزامی است.',
            'password_expiry_days.integer' => 'مهلت باید یک عدد صحیح باشد.',
            'password_expiry_days.min' => 'مهلت نباید کمتر از ۳۰ روز باشد.',
            'password_expiry_days.max' => 'مهلت نباید بیشتر از ۳۶۵ روز باشد.',
        ];
    }
}
