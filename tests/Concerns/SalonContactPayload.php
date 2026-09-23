<?php

namespace Tests\Concerns;

/**
 * ⭐ فیلدهای اجباری «اطلاعات تماس و فعالیت سالن» فرم ثبت‌نام عمومی (۲۰۲۶-۰۹-۲۳) — یک‌جا، تا هر
 * تستی که یک ثبت‌نام معتبر می‌خواد مجبور نباشه این چهار فیلد رو تکرار کنه.
 */
trait SalonContactPayload
{
    protected function salonContactPayload(array $overrides = []): array
    {
        $hours = [];
        foreach ([6, 0, 1, 2, 3] as $day) {
            $hours[$day] = ['open' => '09:00', 'close' => '21:00'];
        }
        $hours[4] = ['open' => '09:00', 'close' => '17:00'];
        $hours[5] = ['closed' => '1'];

        return array_merge([
            'salon_address' => 'تهران، خیابان ولیعصر، پلاک ۱۲',
            'salon_phone' => '02112345678',
            'experience_years' => '7',
            'working_hours' => $hours,
        ], $overrides);
    }
}
