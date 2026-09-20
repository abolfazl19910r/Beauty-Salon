<?php

namespace App\Http\Requests\Admin\DiscountCode;

use App\Rules\MaxPercentage;
use Illuminate\Foundation\Http\FormRequest;

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
            'type' => ['required', 'in:fixed,percentage'],
            'amount' => $amountRules,
            'max_amount' => ['nullable', 'numeric', 'min:0'],
            'base_amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
