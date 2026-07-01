<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBlogPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->is_admin;
    }

    public function rules(): array
    {
        return [
            'title'        => ['required', 'string', 'max:255'],
            'content'      => ['required', 'string'],
            'excerpt'      => ['nullable', 'string'],
            'category_id'  => ['required', 'exists:blog_categories,id'],
            'image'        => ['nullable', 'image', 'max:2048'],
            'is_published' => ['boolean'],
            'published_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'       => 'عنوان پست الزامی است.',
            'content.required'     => 'محتوای پست الزامی است.',
            'category_id.required' => 'دسته‌بندی الزامی است.',
            'category_id.exists'   => 'دسته‌بندی انتخابی معتبر نیست.',
            'image.image'          => 'فایل باید تصویر باشد.',
            'image.max'            => 'حجم تصویر نباید بیشتر از ۲MB باشد.',
        ];
    }
}
