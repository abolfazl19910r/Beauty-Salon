<?php

namespace App\Http\Requests\Specialist\Wallet\Withdrawal;

use App\Models\WalletSetting;
use Illuminate\Foundation\Http\FormRequest;

class StoreWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The actual authorization (wallet ownership) is checked in the controller with Policy(`requestWithdrawal`).
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        $amount = str_replace($persian, $english, (string) $this->input('amount', ''));
        // Remove commas (English and Persian) and spaces.
        $amount = preg_replace('/[,\s٬]+/', '', $amount);
        $amount = preg_replace('/[^0-9]/', '', $amount);

        $this->merge(['amount' => $amount]);
    }

    public function rules(): array
    {
        $minimum = $this->walletSettings()->minimum_withdrawal_amount ?? 0;

        return [
            'amount' => ['required', 'numeric', 'min:'.$minimum],
            'method' => ['required', 'in:instant,iban'],
        ];
    }

    public function messages(): array
    {
        $minimum = $this->walletSettings()->minimum_withdrawal_amount ?? 0;

        return [
            'amount.required' => 'مبلغ الزامی است.',
            'amount.numeric' => 'مبلغ باید عدد باشد.',
            'amount.min' => 'حداقل مبلغ برداشت '.number_format($minimum).' تومان است.',
        ];
    }

    /**
     * WalletSetting has its own custom static get() (first() ?? create([])) specifically because
     * Eloquent's built-in Model::get() returns a Collection — calling that generic method here
     * by mistake once made ->minimum_withdrawal_amount always null (a Collection has no such
     * property), silently turning the `min:` rule into `min:` (empty, i.e. no minimum enforced
     * at all). WalletSetting::get() is the safe one to call — it shadows the generic Eloquent
     * method by design, precisely to avoid that confusion. ::first() (the very thing being
     * guarded against here) is a different bug: it returns null when no row exists yet for the
     * current salon, which the : WalletSetting return type below would turn into a TypeError.
     */
    private function walletSettings(): WalletSetting
    {
        return WalletSetting::get();
    }
}
