<?php

namespace App\Http\Requests\Admin\Loyalty;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLoyaltySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->is_admin;
    }

    public function rules(): array
    {
        return [
            'points_per_booking'      => ['required', 'integer', 'min:0'],
            'points_per_referral'     => ['required', 'integer', 'min:0'],
            'points_expiry_days'      => ['required', 'integer', 'min:0'],
            'min_points_to_redeem'    => ['required', 'integer', 'min:1'],
            'loyalty_program_enabled' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'points_per_booking.required'   => 'امتیاز به ازای هر رزرو الزامی است.',
            'points_per_referral.required'  => 'امتیاز معرفی الزامی است.',
            'points_expiry_days.required'   => 'تعداد روزهای انقضا الزامی است.',
            'min_points_to_redeem.required' => 'حداقل امتیاز برای استفاده الزامی است.',
            'min_points_to_redeem.min'      => 'حداقل امتیاز باید بزرگتر از صفر باشد.',
        ];
    }
}
