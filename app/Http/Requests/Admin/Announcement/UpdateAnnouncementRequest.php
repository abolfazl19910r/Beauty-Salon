<?php

namespace App\Http\Requests\Admin\Announcement;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('access_admin_panel');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'type' => ['required', 'in:general,maintenance,promotion'],
            'priority' => ['required', 'integer', 'min:0'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:published_at'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'عنوان اطلاعیه الزامی است.',
            'content.required' => 'متن اطلاعیه الزامی است.',
            'type.required' => 'نوع اطلاعیه الزامی است.',
            'type.in' => 'نوع اطلاعیه معتبر نیست.',
            'priority.required' => 'اولویت الزامی است.',
            'expires_at.after' => 'تاریخ انقضا باید بعد از تاریخ انتشار باشد.',
        ];
    }
}
