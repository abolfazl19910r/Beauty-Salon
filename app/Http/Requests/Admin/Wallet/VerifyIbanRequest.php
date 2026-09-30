<?php

namespace App\Http\Requests\Admin\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class VerifyIbanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'holder_name_checked' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'holder_name_checked.accepted' => 'پیش از تأیید، شبا را در اپ بانک وارد کنید، نام صاحب حساب را با نام متخصص مقایسه کنید و این گزینه را تیک بزنید.',
        ];
    }
}
