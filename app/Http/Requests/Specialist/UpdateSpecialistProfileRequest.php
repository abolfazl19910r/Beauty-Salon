<?php

namespace App\Http\Requests\Specialist;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSpecialistProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        // ⭐ Fix (test-writing session 6): this controller action operates purely on
        // auth()->user() (self-scoped, no cross-user risk) with no separate Policy
        // check, so auth()->check() is the correct and sufficient gate here. Previously
        // required hasRole('specialist'), which nothing in production ever assigns —
        // see SpecialistPolicy for the full explanation of this bug class.
        return auth()->check();
    }

    public function rules(): array
    {
        $userId = auth()->id();

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', "unique:users,phone,{$userId}"],
            // ⭐ Fix (test-writing session 6): the users table has no email column at all
            // (the whole project is phone-based — same finding already documented for the
            // customer-facing ProfileUpdateRequest). This rule ran a real `unique:users,email`
            // query against a column that doesn't exist, so submitting any non-empty value in
            // the form's optional "email" field crashed with a fatal SQL error. Removed.
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'نام الزامی است.',
            'name.max' => 'نام نباید بیشتر از ۲۵۵ کاراکتر باشد.',
            'phone.required' => 'شماره موبایل الزامی است.',
            'phone.unique' => 'این شماره موبایل قبلاً توسط کاربر دیگری استفاده شده.',
        ];
    }
}
