<?php

namespace App\Http\Requests\Admin\SupportTicket;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'category' => ['required', 'string', 'in:booking,payment,service,technical,other'],
            'priority' => ['sometimes', 'in:low,medium,high,urgent'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'لطفاً عنوان تیکت را وارد کنید.',
            'description.required' => 'لطفاً توضیحات تیکت را وارد کنید.',
            'description.max' => 'توضیحات نباید بیشتر از ۵۰۰۰ کاراکتر باشد.',
            'category.required' => 'لطفاً دسته‌بندی تیکت را انتخاب کنید.',
            'category.in' => 'دسته‌بندی انتخاب‌شده معتبر نیست.',
            'priority.in' => 'اولویت انتخاب‌شده معتبر نیست.',
        ];
    }
}
