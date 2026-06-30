<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class ProcessPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'use_wallet'    => 'required|boolean',
            'wallet_amount' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'use_wallet.required' => 'انتخاب روش پرداخت الزامی است.',
            'use_wallet.boolean'  => 'مقدار استفاده از کیف پول معتبر نیست.',
            'wallet_amount.numeric' => 'مبلغ کیف پول باید عدد باشد.',
            'wallet_amount.min'     => 'مبلغ کیف پول نمی‌تواند منفی باشد.',
        ];
    }
}
