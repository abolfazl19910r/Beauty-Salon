<?php

namespace App\Http\Requests\Admin\DiscountCode;

use App\Rules\MaxPercentage;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Backs the live "پیش‌نمایش تخفیف" widget on the create form: lets the admin see, before saving,
 * what a given type/amount/max_amount combination would actually discount off a sample amount —
 * computed via App\Services\Discount\DiscountCalculator (the project's single source of discount
 * math, per R-DiscountLogic), not reimplemented in JS.
 */
class PreviewDiscountCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('access_admin_panel');
    }

    public function rules(): array
    {
        $amountRules = ['required', 'numeric', 'min:0'];

        if ($this->input('type') === 'percentage') {
            $amountRules[] = new MaxPercentage;
        }

        return [
            'type'        => ['required', 'in:fixed,percentage'],
            'amount'      => $amountRules,
            'max_amount'  => ['nullable', 'numeric', 'min:0'],
            'base_amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
