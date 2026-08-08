<?php

namespace App\Http\Requests\Admin\Wallet;

use App\Rules\MaxPercentage;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWalletSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('access_admin_panel');
    }

    public function rules(): array
    {
        return [
            'withdrawal_fee_percentage' => ['required', 'numeric', 'min:0', new MaxPercentage],
            'minimum_withdrawal_amount' => ['required', 'numeric', 'min:0'],
            'maximum_withdrawal_amount' => ['required', 'numeric', 'min:0'],
            'instant_withdrawal_enabled' => ['nullable', 'boolean'],
            'instant_withdrawal_fee' => ['required_if:instant_withdrawal_enabled,1', 'numeric', 'min:0'],
            'cancellation_before_hours' => ['required', 'integer', 'min:1'],
            'customer_cancellation_fee_percentage' => ['required', 'numeric', 'min:0', new MaxPercentage],
            'specialist_cancellation_penalty_percentage' => ['required', 'numeric', 'min:0', new MaxPercentage],
            'specialist_cancellation_before_hours' => ['required', 'integer', 'min:1'],
            'specialist_repeat_cancellation_threshold' => ['required', 'integer', 'min:0'],
            'specialist_repeat_cancellation_window_days' => ['required', 'integer', 'min:1'],
            'specialist_repeat_cancellation_extra_percentage' => ['required', 'numeric', 'min:0', new MaxPercentage],
            'settlement_delay_days' => ['required', 'integer', 'min:0', 'max:30'],
            'admin_commission_percentage' => ['required', 'numeric', 'min:0', new MaxPercentage],
            'prepayment_percentage' => ['required', 'numeric', 'min:0', new MaxPercentage],
            'minimum_prepayment_amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
