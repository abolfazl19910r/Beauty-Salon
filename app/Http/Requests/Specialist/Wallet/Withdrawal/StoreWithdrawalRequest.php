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
     * ⚠️ Bugfix: old controller here from WalletSetting::get() (which returns a Collection)
     * used, not ::first() (which returns a single record). Because the configuration table has only one row,
     * Access to ->minimum_withdrawal_amount on a Collection was always null and the rule min: actually
     * would be converted to empty (no number) `min:`.
 */
    private function walletSettings(): WalletSetting
    {
        return WalletSetting::first();
    }
}
