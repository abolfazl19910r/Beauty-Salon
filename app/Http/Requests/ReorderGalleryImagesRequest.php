<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderGalleryImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->is_admin;
    }

    public function rules(): array
    {
        return [
            'images'         => ['required', 'array'],
            'images.*.id'    => ['required', 'exists:gallery_images,id'],
            'images.*.order' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'images.required'         => 'لیست تصاویر الزامی است.',
            'images.*.id.required'    => 'شناسه‌ی تصویر الزامی است.',
            'images.*.id.exists'      => 'تصویر مورد نظر یافت نشد.',
            'images.*.order.required' => 'ترتیب تصویر الزامی است.',
            'images.*.order.integer'  => 'ترتیب باید عدد صحیح باشد.',
        ];
    }
}
