<?php

namespace App\Http\Requests\Admin\Blog\Category;

use App\Support\CurrentSalon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBlogCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('access_admin_panel');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('blog_categories', 'name')->where('salon_id', app(CurrentSalon::class)->id())],
            'description' => ['nullable', 'string', 'max:500'],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'نام دسته‌بندی الزامی است.',
            'name.unique' => 'این نام دسته‌بندی قبلاً استفاده شده است.',
        ];
    }
}
