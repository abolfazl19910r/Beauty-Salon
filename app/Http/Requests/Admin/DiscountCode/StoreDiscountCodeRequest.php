<?php

namespace App\Http\Requests\Admin\DiscountCode;

use App\Rules\MaxPercentage;
use Illuminate\Foundation\Http\FormRequest;

class StoreDiscountCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('access_admin_panel');
    }

    public function rules(): array
    {
        $amountRules = ['required', 'numeric', 'min:1'];

        if ($this->input('type') === 'percentage') {
            $amountRules[] = new MaxPercentage;
        }

        return [
            'code' => ['required', 'string', 'unique:discount_codes,code'],
            'type' => ['required', 'in:fixed,percentage'],
            'amount' => $amountRules,
            'max_uses' => ['required', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date', 'after:today'],
            'user_id' => ['nullable', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'کد تخفیف الزامی است.',
            'code.unique' => 'این کد تخفیف قبلاً استفاده شده است.',
            'type.required' => 'نوع تخفیف الزامی است.',
            'type.in' => 'نوع تخفیف باید fixed یا percentage باشد.',
            'amount.required' => 'مقدار تخفیف الزامی است.',
            'amount.numeric' => 'مقدار تخفیف باید عدد باشد.',
            'amount.min' => 'مقدار تخفیف باید حداقل ۱ باشد.',
            'max_uses.required' => 'حداکثر تعداد استفاده الزامی است.',
            'expires_at.after' => 'تاریخ انقضا باید بعد از امروز باشد.',
        ];
    }
}
