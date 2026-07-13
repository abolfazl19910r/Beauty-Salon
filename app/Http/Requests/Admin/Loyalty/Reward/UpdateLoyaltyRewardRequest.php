<?php

namespace App\Http\Requests\Admin\Loyalty\Reward;

use App\Rules\MaxPercentage;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLoyaltyRewardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->is_admin;
    }

    public function rules(): array
    {
        $reward = $this->route('reward');

        return [
            'title'           => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'required_points' => ['required', 'integer', 'min:1'],
            'discount_type'   => ['required', 'in:fixed,percentage'],
            'discount_amount' => ['required', 'numeric', 'min:1', new MaxPercentage],
            'max_uses'        => ['required', 'integer', 'min:' . ($reward?->used_count ?? 0)],
            'is_active'       => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'max_uses.min' => 'حداکثر استفاده نمی‌تواند از تعداد استفاده‌های فعلی کمتر باشد.',
        ];
    }
}
