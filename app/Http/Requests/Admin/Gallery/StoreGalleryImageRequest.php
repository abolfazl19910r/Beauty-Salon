<?php

namespace App\Http\Requests\Admin\Gallery;

use Illuminate\Foundation\Http\FormRequest;

class StoreGalleryImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->is_admin;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['required', 'image', \App\Support\ImageUpload::EXTENSIONS, 'max:2048'],
            'order' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'عنوان تصویر الزامی است.',
            'image.required' => 'تصویر الزامی است.',
            'image.image' => 'فایل باید تصویر باشد.',
            'image.extensions' => \App\Support\ImageUpload::EXTENSION_MESSAGE,
            'image.max' => 'حجم تصویر نباید بیشتر از ۲MB باشد.',
        ];
    }
}
