<?php

namespace App\Support;

/**
 * ⭐ ساعات کاری هر سالن (۲۰۲۶-۰۹-۲۳) — تبدیل ورودی فرم به شکل ذخیره‌شده، اعتبارسنجی، و متن
 * نمایشی. کلیدها روز هفته‌ی Carbon هستن (۰=یکشنبه … ۶=شنبه، همون قرارداد
 * specialist_schedules.day_of_week)؛ مقدار هر روز یا ['open' => 'H:i', 'close' => 'H:i'] یا null
 * (تعطیل). این فقط برای نمایش به مشتریه — زمان‌های قابل‌رزرو همچنان فقط از برنامه‌ی کاری هر
 * متخصص میان، نه از اینجا.
 */
class SalonWorkingHours
{
    /** ترتیب هفته‌ی ایرانی: شنبه → جمعه. */
    public const WEEK_ORDER = [6, 0, 1, 2, 3, 4, 5];

    public const DAY_NAMES = [
        6 => 'شنبه',
        0 => 'یکشنبه',
        1 => 'دوشنبه',
        2 => 'سه‌شنبه',
        3 => 'چهارشنبه',
        4 => 'پنجشنبه',
        5 => 'جمعه',
    ];

    /** پیش‌فرض فرم — همون ساعاتی که قبلاً در فوتر هاردکد بود. */
    public static function defaults(): array
    {
        $hours = [];
        foreach (self::WEEK_ORDER as $day) {
            $hours[$day] = match ($day) {
                5 => null,
                4 => ['open' => '09:00', 'close' => '17:00'],
                default => ['open' => '09:00', 'close' => '21:00'],
            };
        }

        return $hours;
    }

    /**
     * ورودی فرم (working_hours[{day}][open|close|closed]) → شکل ذخیره‌شده. فرض می‌کنه قبلش
     * errors() خالی برگردونده.
     */
    public static function fromInput(array $input): array
    {
        $hours = [];
        foreach (self::WEEK_ORDER as $day) {
            $row = $input[$day] ?? $input[(string) $day] ?? [];
            $hours[$day] = ! empty($row['closed'])
                ? null
                : ['open' => (string) $row['open'], 'close' => (string) $row['close']];
        }

        return $hours;
    }

    /**
     * خطاهای اعتبارسنجی به شکل [field => message]؛ خالی یعنی معتبر.
     */
    public static function errors(mixed $input): array
    {
        if (! is_array($input)) {
            return ['working_hours' => 'ساعات کاری سالن را وارد کنید.'];
        }

        $errors = [];
        $openDays = 0;

        foreach (self::WEEK_ORDER as $day) {
            $row = $input[$day] ?? $input[(string) $day] ?? null;
            $name = self::DAY_NAMES[$day];

            if (! is_array($row)) {
                $errors["working_hours.{$day}"] = "ساعات کاری {$name} را مشخص کنید.";

                continue;
            }

            if (! empty($row['closed'])) {
                continue;
            }

            $open = $row['open'] ?? null;
            $close = $row['close'] ?? null;

            if (! self::isTime($open) || ! self::isTime($close)) {
                $errors["working_hours.{$day}"] = "ساعت شروع و پایان {$name} را درست وارد کنید (مثلاً ۰۹:۰۰).";

                continue;
            }

            if ($close <= $open) {
                $errors["working_hours.{$day}"] = "ساعت پایان {$name} باید بعد از ساعت شروع باشد.";

                continue;
            }

            $openDays++;
        }

        if ($errors === [] && $openDays === 0) {
            $errors['working_hours'] = 'حداقل یک روز از هفته باید باز باشد.';
        }

        return $errors;
    }

    /**
     * خطوط نمایشی، با ادغام روزهای پشت‌سرهم با ساعت یکسان:
     * ['شنبه تا چهارشنبه: ۰۹:۰۰ تا ۲۱:۰۰', 'پنجشنبه: ۰۹:۰۰ تا ۱۷:۰۰', 'جمعه: تعطیل'].
     */
    public static function lines(?array $hours): array
    {
        if (! $hours) {
            return [];
        }

        $groups = [];
        foreach (self::WEEK_ORDER as $day) {
            $value = $hours[$day] ?? $hours[(string) $day] ?? null;
            $last = array_key_last($groups);

            if ($last !== null && $groups[$last]['value'] == $value) {
                $groups[$last]['to'] = $day;
            } else {
                $groups[] = ['from' => $day, 'to' => $day, 'value' => $value];
            }
        }

        return array_map(function (array $group) {
            $days = $group['from'] === $group['to']
                ? self::DAY_NAMES[$group['from']]
                : self::DAY_NAMES[$group['from']].' تا '.self::DAY_NAMES[$group['to']];

            $time = $group['value'] === null
                ? 'تعطیل'
                : to_persian_num($group['value']['open']).' تا '.to_persian_num($group['value']['close']);

            return "{$days}: {$time}";
        }, $groups);
    }

    private static function isTime(mixed $value): bool
    {
        return is_string($value) && preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $value) === 1;
    }
}
