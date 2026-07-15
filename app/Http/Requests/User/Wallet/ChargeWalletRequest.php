<?php

namespace App\Http\Requests\User\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class ChargeWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $english = ['0','1','2','3','4','5','6','7','8','9'];

        $amount = str_replace($persian, $english, $this->input('amount', ''));
        $amount = preg_replace('/[^0-9]/', '', $amount);

        $this->merge(['amount' => $amount]);
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:10000|max:50000000',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'لطفاً مبلغ شارژ را وارد کنید.',
            'amount.numeric'  => 'مبلغ باید عدد باشد.',
            'amount.min'      => 'حداقل مبلغ شارژ ۱۰٬۰۰۰ تومان است.',
            'amount.max'      => 'حداکثر مبلغ شارژ ۵۰٬۰۰۰٬۰۰۰ تومان است.',
        ];
    }
}
