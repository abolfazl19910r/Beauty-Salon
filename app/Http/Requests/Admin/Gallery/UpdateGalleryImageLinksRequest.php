<?php

namespace App\Http\Requests\Admin\Gallery;

use Illuminate\Foundation\Http\FormRequest;

/** تغییر پیوند خدمت/کارشناسِ یک نمونه‌کار موجود (بسته‌ی ۵ب). */
class UpdateGalleryImageLinksRequest extends FormRequest
{
    use GalleryLinkRules;

    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->is_admin;
    }

    public function rules(): array
    {
        return $this->linkRules();
    }
}
