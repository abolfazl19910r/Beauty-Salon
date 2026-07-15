<?php

namespace App\Http\Requests\Admin\Blog\Post;

use Illuminate\Foundation\Http\FormRequest;
use Morilog\Jalali\Jalalian;

class StoreAdminBlogPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('access_admin_panel');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'category_id' => ['required', 'exists:blog_categories,id'],
            'image' => ['nullable', 'image', 'max:2048'],
            'is_published' => ['nullable'],
            'published_at_jalali' => [
                'nullable',
                'string',
                'regex:/^\d{4}\/\d{2}\/\d{2}\s\d{2}:\d{2}$/',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    try {
                        Jalalian::fromFormat('Y/m/d H:i', $value);
                    } catch (\Throwable) {
                        $fail('فرمت تاریخ نامعتبر است. فرمت صحیح: YYYY/MM/DD HH:MM');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'عنوان مقاله الزامی است.',
            'content.required' => 'محتوای مقاله الزامی است.',
            'category_id.required' => 'دسته‌بندی الزامی است.',
            'category_id.exists' => 'دسته‌بندی انتخابی معتبر نیست.',
            'image.image' => 'فایل باید تصویر باشد.',
            'image.max' => 'حجم تصویر نباید بیشتر از ۲ مگابایت باشد.',
        ];
    }
}
