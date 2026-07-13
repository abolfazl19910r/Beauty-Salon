<?php

namespace App\Http\Requests\Admin\Loyalty\Reward;

use Illuminate\Foundation\Http\FormRequest;

class RedeemRewardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->is_admin;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'کاربر الزامی است.',
            'user_id.exists'   => 'کاربر انتخابی معتبر نیست.',
        ];
    }
}
