<?php

namespace App\Http\Requests\Admin\Specialist;

use App\Rules\MaxPercentage;
use Illuminate\Foundation\Http\FormRequest;

class StoreSpecialistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('access_admin_panel');
    }

    public function rules(): array
    {
        return [
            'name'            => ['required', 'string', 'max:255'],
            'phone'           => ['required', 'string', 'max:11', 'unique:specialists,phone'],
            'email'           => ['required', 'email', 'unique:specialists,email'],
            'services'        => ['required', 'array'],
            'services.*'      => ['exists:beauty_services,id'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', new MaxPercentage],
        ];
    }
}
