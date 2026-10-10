<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Specialist\UpdateScheduleRequest;

/**
 * برنامه‌ی هفتگی از اپ همکار — همان قواعد فرم وب (UpdateScheduleRequest) به‌علاوه‌ی:
 *  - بولی JSON (true/false) برای is_active و auto_confirm_bookings به 1/0 تبدیل می‌شود (required_if:...,1 وب)؛
 *  - هر روز هفته حداکثر یک بار (فرم وب هفت ردیف ثابت دارد، اپ آرایه‌ی آزاد می‌فرستد)؛ ساعت‌ها H:i.
 */
class StaffScheduleRequest extends UpdateScheduleRequest
{
    protected function prepareForValidation(): void
    {
        $schedules = collect($this->input('schedules', []))
            ->map(fn ($row) => is_array($row) && array_key_exists('is_active', $row)
                ? array_merge($row, ['is_active' => filter_var($row['is_active'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0])
                : $row)
            ->all();

        $merge = ['schedules' => $schedules];
        if ($this->has('auto_confirm_bookings') && is_bool($this->input('auto_confirm_bookings'))) {
            $merge['auto_confirm_bookings'] = $this->input('auto_confirm_bookings') ? 1 : 0;
        }

        $this->merge($merge);
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'schedules' => 'present|array|max:7',
            'schedules.*.day_of_week' => 'required|integer|between:0,6|distinct',
            'schedules.*.start_time' => 'nullable|date_format:H:i|required_if:schedules.*.is_active,1',
            'schedules.*.end_time' => 'nullable|date_format:H:i|required_if:schedules.*.is_active,1|after:schedules.*.start_time',
            'schedules.*.break_start' => 'nullable|date_format:H:i|required_with:schedules.*.break_end|after:schedules.*.start_time|before:schedules.*.end_time',
            'schedules.*.break_end' => 'nullable|date_format:H:i|required_with:schedules.*.break_start|after:schedules.*.break_start|before_or_equal:schedules.*.end_time',
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'schedules.*.day_of_week.distinct' => 'هر روز هفته فقط یک بار.',
        ]);
    }
}
