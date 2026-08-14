<?php

namespace App\Http\Requests\Admin\Loyalty\Reward;

use App\Rules\MaxPercentage;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate loyalty reward creation — replaces validate inline in
 * AdminLoyaltyController::store() and AdminLoyaltyController::storeReward()
 */
class StoreLoyaltyRewardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('access_admin_panel');
    }

    public function rules(): array
    {
        $discountAmountRules = ['required', 'numeric', 'min:1'];

        // MaxPercentage only makes sense for percentage-type rewards; a 'fixed' discount_amount
        // is a toman value (typically tens of thousands) and must not be capped at 100. Applying
        // it unconditionally is the exact same regression documented for StoreDiscountCodeRequest
        // in R-AdminForms — it was fixed there but never carried over to this sibling Form Request.
        if ($this->input('discount_type') === 'percentage') {
            $discountAmountRules[] = new MaxPercentage;
        }

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'required_points' => ['required', 'integer', 'min:1'],
            'discount_type' => ['required', 'in:fixed,percentage'],
            'discount_amount' => $discountAmountRules,
            'max_uses' => ['required', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'عنوان پاداش الزامی است.',
            'required_points.required' => 'امتیاز موردنیاز الزامی است.',
            'required_points.min' => 'امتیاز موردنیاز باید حداقل ۱ باشد.',
            'discount_type.required' => 'نوع تخفیف الزامی است.',
            'discount_type.in' => 'نوع تخفیف باید fixed یا percentage باشد.',
            'discount_amount.required' => 'مقدار تخفیف الزامی است.',
            'discount_amount.min' => 'مقدار تخفیف باید حداقل ۱ باشد.',
            'max_uses.required' => 'حداکثر تعداد استفاده الزامی است.',
        ];
    }
}
