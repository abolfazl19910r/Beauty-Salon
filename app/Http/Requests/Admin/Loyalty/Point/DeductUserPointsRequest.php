<?php

namespace App\Http\Requests\Admin\Loyalty\Point;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a manual points deduction by an admin from a specific user
 * (App\Services\Admin\Loyalty\LoyaltyAdminService::deductPoints()).
 *
 * Insufficient-balance is NOT checked here — that is a business-logic
 * concern handled by the service (InsufficientLoyaltyPointsException),
 * not a validation-shape concern.
 */
class DeductUserPointsRequest extends FormRequest
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
        ];
    }

    public function messages(): array
    {
        return [
            'points.required' => 'تعداد امتیاز الزامی است.',
            'points.min' => 'تعداد امتیاز باید حداقل ۱ باشد.',
            'description.required' => 'توضیح دلیل کسر امتیاز الزامی است.',
        ];
    }
}
