<?php

namespace App\Http\Requests\Admin\Loyalty;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLoyaltySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('access_admin_panel');
    }

    public function rules(): array
    {
        return [
            'points_per_amount' => ['sometimes', 'required', 'numeric', 'min:1'],
            'points_expiry_months' => ['sometimes', 'required', 'integer', 'min:1'],
            'minimum_points_for_discount' => ['sometimes', 'required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'points_per_amount.required' => 'مبلغ به ازای هر امتیاز الزامی است.',
            'points_per_amount.min' => 'مبلغ باید حداقل ۱ تومان باشد.',
            'points_expiry_months.required' => 'مدت اعتبار امتیاز (ماه) الزامی است.',
            'minimum_points_for_discount.required' => 'حداقل امتیاز برای دریافت تخفیف الزامی است.',
        ];
    }
}
