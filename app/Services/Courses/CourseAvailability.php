<?php

namespace App\Services\Courses;

use App\Models\CustomerCourse;
use App\Models\Specialist;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * روزها و ساعت‌های آزاد برای جلسه‌ی بعد یک دوره (وب و اپ): فقط در بازه‌ی مجاز دوره (زودترین روز تا پایان مهلت) و با مدت خدمت.
 * کادر (byStaff) از امروز می‌بیند — فاصله‌ی حداقل را می‌تواند دور بزند.
 */
class CourseAvailability
{
    public const WINDOW_DAYS = 45;

    /** متخصص‌هایی که این دوره را انجام می‌دهند (ثابت دوره، یا همه‌ی متخصص‌های فعال آن خدمت) */
    public function specialists(CustomerCourse $course): Collection
    {
        $query = Specialist::query()
            ->whereHas('services', fn ($q) => $q->whereKey($course->service_id));

        return $course->specialist_id ? $query->whereKey($course->specialist_id)->get() : $query->orderBy('name')->get();
    }

    public function window(CustomerCourse $course, bool $byStaff = false): array
    {
        $from = $byStaff ? now()->startOfDay() : $course->earliestNextSession();
        $to = $from->copy()->addDays(self::WINDOW_DAYS);
        if ($course->expires_at && $course->expires_at->lt($to)) {
            $to = $course->expires_at->copy()->startOfDay();
        }

        return [$from, $to];
    }

    /** @return list<string> Y-m-d */
    public function dates(CustomerCourse $course, Specialist $specialist, bool $byStaff = false): array
    {
        [$from, $to] = $this->window($course, $byStaff);
        $duration = (int) ($course->service?->duration ?: 30);
        $dates = [];

        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            if ($this->slots($course, $specialist, $day->format('Y-m-d'), $duration) !== []) {
                $dates[] = $day->format('Y-m-d');
            }
        }

        return $dates;
    }

    /** @return list<string> H:i */
    public function slots(CustomerCourse $course, Specialist $specialist, string $date, ?int $duration = null): array
    {
        $duration ??= (int) ($course->service?->duration ?: 30);
        $slots = $specialist->getAvailableSlots($date, $duration);

        // امروز: فقط ساعت‌های آینده
        if (Carbon::parse($date)->isToday()) {
            $slots = array_values(array_filter($slots, fn ($t) => Carbon::parse($date.' '.$t)->isFuture()));
        }

        return array_values($slots);
    }
}
