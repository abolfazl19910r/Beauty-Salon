<?php

namespace App\Http\Requests\Specialist;

use App\Models\WalletSetting;
use Illuminate\Foundation\Http\FormRequest;

class StoreWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('specialist');
    }

    public function rules(): array
    {
        $minAmount = WalletSetting::get()->minimum_withdrawal_amount ?? 50000;

        return [
            'amount' => "required|numeric|min:{$minAmount}",
            'method' => 'required|in:instant,iban',
        ];
    }

    public function messages(): array
    {
        $minAmount = WalletSetting::get()->minimum_withdrawal_amount ?? 50000;

        return [
            'amount.required' => 'مبلغ برداشت الزامی است.',
            'amount.numeric'  => 'مبلغ باید عدد باشد.',
            'amount.min'      => 'حداقل مبلغ برداشت ' . number_format($minAmount) . ' تومان است.',
            'method.required' => 'روش برداشت الزامی است.',
            'method.in'       => 'روش برداشت انتخابی معتبر نیست.',
        ];
    }
}
