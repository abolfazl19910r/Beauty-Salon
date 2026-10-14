<?php

namespace Tests\Concerns;

use App\Models\BeautyService;
use App\Models\CoursePackage;
use App\Models\CustomerCourse;
use App\Models\Specialist;
use App\Models\SpecialistSchedule;
use App\Models\User;
use App\Services\Courses\CourseService;
use App\Support\CurrentSalon;
use Illuminate\Support\Carbon;

/** راه‌اندازی مشترک تست‌های دوره‌ی درمانی (بسته‌ی ب): خدمت، متخصص با ساعت کاری هر روز، بسته و مشتری سالن جاری */
trait CreatesCourses
{
    protected BeautyService $courseService;

    protected Specialist $courseSpecialist;

    protected User $courseCustomer;

    protected function setUpCourseWorld(): void
    {
        $salon = app(CurrentSalon::class)->get();
        $this->courseService = BeautyService::factory()->create(['name' => 'لیزر صورت', 'duration' => 30, 'price' => 1000000]);
        $this->courseSpecialist = Specialist::factory()->create(['auto_confirm_bookings' => true]);
        $this->courseSpecialist->services()->attach($this->courseService->id);
        foreach (range(0, 6) as $day) {
            SpecialistSchedule::factory()->create([
                'specialist_id' => $this->courseSpecialist->id, 'day_of_week' => $day,
                'start_time' => '08:00', 'end_time' => '20:00', 'break_start' => null, 'break_end' => null, 'is_active' => true,
            ]);
        }
        $this->courseCustomer = User::factory()->create(['user_type' => 'customer', 'salon_id' => $salon->id, 'phone' => '0935'.random_int(1000000, 9999999)]);
    }

    protected function coursePackage(array $attributes = []): CoursePackage
    {
        return CoursePackage::create($attributes + [
            'service_id' => $this->courseService->id, 'name' => 'لیزر صورت ۶ جلسه', 'sessions_count' => 6, 'price' => 4800000,
            'interval_days' => 35, 'min_interval_days' => 28, 'validity_days' => 365, 'is_active' => true, 'is_public' => true,
        ]);
    }

    /** دوره‌ی فعال و کامل پرداخت‌شده (حضوری مگر خلافش گفته شود) */
    protected function paidCourse(array $package = [], string $method = 'in_person', ?float $amount = null): CustomerCourse
    {
        $course = app(CourseService::class)->purchase($this->courseCustomer, $this->coursePackage($package));
        app(CourseService::class)->recordPayment($course, $amount ?? $course->price_total, $method);

        return $course->fresh();
    }

    /** پلن سالن جاری بدون این امکان‌ها (تنزل پلن) */
    protected function courseSalonWithout(\App\Support\Features\Feature ...$off): \App\Models\Salon
    {
        $plan = \App\Models\Plan::create(['key' => 'p'.uniqid(), 'name' => 'پایه', 'price_1m' => 990000, 'sms_quota_per_month' => 1000]);
        $plan->syncFeatures(array_diff(\App\Support\Features\Feature::keys(), array_map(fn ($f) => $f->value, $off)));
        $salon = app(CurrentSalon::class)->get();
        $salon->update(['plan_id' => $plan->id]);
        app(\App\Services\Plans\FeatureResolver::class)->forget($salon->id);

        return $salon->fresh();
    }

    protected function sessionAt(int $daysFromNow, string $time = '10:00'): string
    {
        return Carbon::now()->addDays($daysFromNow)->format('Y-m-d').' '.$time.':00';
    }
}
