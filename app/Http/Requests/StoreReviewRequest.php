<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => 'required|string',
            'overall_rating' => 'required|integer|min:1|max:5',
            'quality_rating' => 'required|integer|min:1|max:5',
            'behavior_rating' => 'required|integer|min:1|max:5',
            'cleanliness_rating' => 'required|integer|min:1|max:5',
            'speed_rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'overall_rating.required' => 'لطفاً امتیاز کلی را وارد کنید.',
            'overall_rating.min' => 'امتیاز باید حداقل 1 باشد.',
            'overall_rating.max' => 'امتیاز باید حداکثر 5 باشد.',
            'quality_rating.required' => 'لطفاً امتیاز کیفیت کار را وارد کنید.',
            'behavior_rating.required' => 'لطفاً امتیاز رفتار متخصص را وارد کنید.',
            'cleanliness_rating.required' => 'لطفاً امتیاز تمیزی را وارد کنید.',
            'speed_rating.required' => 'لطفاً امتیاز سرعت را وارد کنید.',
            'comment.max' => 'نظر شما نباید بیشتر از 500 کاراکتر باشد.',
        ];
    }
}
