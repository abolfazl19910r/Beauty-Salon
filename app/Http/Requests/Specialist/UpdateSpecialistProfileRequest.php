<?php

namespace App\Http\Requests\Specialist;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSpecialistProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('specialist');
    }

    public function rules(): array
    {
        $userId = auth()->id();

        return [
            'name'  => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', "unique:users,phone,{$userId}"],
            'email' => ['nullable', 'email', 'max:255', "unique:users,email,{$userId}"],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'   => 'نام الزامی است.',
            'name.max'        => 'نام نباید بیشتر از ۲۵۵ کاراکتر باشد.',
            'phone.required'  => 'شماره موبایل الزامی است.',
            'phone.unique'    => 'این شماره موبایل قبلاً توسط کاربر دیگری استفاده شده.',
            'email.email'     => 'فرمت ایمیل معتبر نیست.',
            'email.unique'    => 'این ایمیل قبلاً توسط کاربر دیگری استفاده شده.',
        ];
    }
}
