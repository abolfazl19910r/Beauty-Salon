<?php

namespace App\Http\Requests\Admin\Loyalty\Point;

use Illuminate\Foundation\Http\FormRequest;

class AddUserPointsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('access_admin_panel');
    }

    public function rules(): array
    {
        return [
            'points'      => ['required', 'integer', 'min:1', 'max:10000'],
            'description' => ['required', 'string', 'max:255'],
            'expires_at'  => ['nullable', 'date', 'after:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'points.required'      => 'تعداد امتیاز الزامی است.',
            'points.min'           => 'تعداد امتیاز باید حداقل ۱ باشد.',
            'points.max'           => 'حداکثر ۱۰٬۰۰۰ امتیاز در یک بار اضافه می‌شود.',
            'description.required' => 'توضیحات الزامی است.',
            'expires_at.after'     => 'تاریخ انقضا باید بعد از امروز باشد.',
        ];
    }
}
