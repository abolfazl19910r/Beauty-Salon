<?php

namespace App\Http\Requests\Specialist;

use Illuminate\Foundation\Http\FormRequest;

class UpdateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        // ⭐ Fix (test-writing session 6): real authorization happens in the controller
        // via SpecialistPolicy::manageSchedule — see that class for why
        // hasRole('specialist') was removed there.
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'schedules.*.day_of_week' => 'required|integer|between:0,6',
            'schedules.*.is_active' => 'nullable',
            'schedules.*.start_time' => 'nullable|required_if:schedules.*.is_active,1',
            'schedules.*.end_time' => 'nullable|required_if:schedules.*.is_active,1|after:schedules.*.start_time',
            'schedules.*.break_start' => 'nullable|required_with:schedules.*.break_end|after:schedules.*.start_time|before:schedules.*.end_time',
            'schedules.*.break_end' => 'nullable|required_with:schedules.*.break_start|after:schedules.*.break_start|before_or_equal:schedules.*.end_time',
            'auto_confirm_bookings' => 'nullable|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'schedules.*.day_of_week.required' => 'روز هفته الزامی است.',
            'schedules.*.day_of_week.between' => 'روز هفته باید بین ۰ تا ۶ باشد.',
            'schedules.*.start_time.required_if' => 'ساعت شروع برای روزهای فعال الزامی است.',
            'schedules.*.end_time.required_if' => 'ساعت پایان برای روزهای فعال الزامی است.',
            'schedules.*.end_time.after' => 'ساعت پایان باید بعد از ساعت شروع باشد.',
            'schedules.*.break_start.required_with' => 'اگر ساعت پایان استراحت وارد شده، ساعت شروع استراحت هم الزامی است.',
            'schedules.*.break_start.after' => 'ساعت شروع استراحت باید بعد از ساعت شروع کار باشد.',
            'schedules.*.break_start.before' => 'ساعت شروع استراحت باید قبل از ساعت پایان کار باشد.',
            'schedules.*.break_end.required_with' => 'اگر ساعت شروع استراحت وارد شده، ساعت پایان استراحت هم الزامی است.',
            'schedules.*.break_end.after' => 'ساعت پایان استراحت باید بعد از ساعت شروع استراحت باشد.',
            'schedules.*.break_end.before_or_equal' => 'ساعت پایان استراحت نمی‌تواند بعد از ساعت پایان کار باشد.',
        ];
    }
}
