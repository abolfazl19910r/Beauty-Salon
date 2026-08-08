<?php

namespace App\Http\Requests\Admin\Loyalty\Point;

use Illuminate\Foundation\Http\FormRequest;

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
            'description.required' => 'توضیحات الزامی است.',
        ];
    }
}
