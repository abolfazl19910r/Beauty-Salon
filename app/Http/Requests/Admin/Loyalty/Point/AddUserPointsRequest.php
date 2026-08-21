<?php

namespace App\Http\Requests\Admin\Loyalty\Point;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a manual points grant by an admin to a specific user
 * (App\Services\Admin\Loyalty\LoyaltyAdminService::addPoints()).
 */
class AddUserPointsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('access_admin_panel');
    }

    public function rules(): array
    {
        return [
            'points' => ['required', 'integer', 'min:1'],
            'description' => ['required', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'points.required' => 'تعداد امتیاز الزامی است.',
            'points.min' => 'تعداد امتیاز باید حداقل ۱ باشد.',
            'description.required' => 'توضیح دلیل افزودن امتیاز الزامی است.',
            'expires_at.after' => 'تاریخ انقضا باید بعد از امروز باشد.',
        ];
    }
}
