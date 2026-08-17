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
        ];
    }
}
