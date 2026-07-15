<?php

namespace App\Http\Requests\Admin\DiscountCode;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDiscountCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('access_admin_panel');
    }

    public function rules(): array
    {
        $discountCode = $this->route('discountCode') ?? $this->route('discount_code');
        $minUses = $discountCode?->used_count ?? 0;

        return [
            'is_active'  => ['boolean'],
            'expires_at' => ['nullable', 'date', 'after:today'],
            'max_uses'   => ['sometimes', 'integer', "min:{$minUses}"],
        ];
    }

    public function messages(): array
    {
        return [
            'expires_at.after' => 'تاریخ انقضا باید بعد از امروز باشد.',
            'max_uses.min'     => 'حداکثر استفاده نمی‌تواند کمتر از تعداد استفاده‌های فعلی باشد.',
        ];
    }
}
