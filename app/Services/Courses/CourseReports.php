<?php

namespace App\Services\Courses;

use App\Models\Booking;
use App\Models\CustomerCourse;
use App\Models\CustomerCoursePayment;
use Illuminate\Support\Carbon;

/**
 * اعداد دوره‌ها برای گزارش مدیر (بسته‌ی ب): فروش دوره جدا از درآمد نوبت، جلسه‌های ارائه‌شده و «ارزش جلسه‌های پیش‌فروش‌شده‌ی مانده»
 * (بدهی خدمت سالن به مشتری‌ها). همه از scope سالن جاری.
 */
class CourseReports
{
    /** @return array{courses_sold: int, sales_amount: float, sessions_delivered: int, liability: float} */
    public function summary(Carbon $from, Carbon $to): array
    {
        return [
            'courses_sold' => CustomerCourse::query()->whereNotNull('purchased_at')->whereBetween('purchased_at', [$from, $to])->count(),
            'sales_amount' => (float) CustomerCoursePayment::query()->whereBetween('created_at', [$from, $to])->sum('amount'),
            'sessions_delivered' => Booking::query()->whereNotNull('customer_course_id')->where('status', 'completed')->whereBetween('booking_time', [$from, $to])->count(),
            'liability' => $this->liability(),
        ];
    }

    /** ارزش جلسه‌های پرداخت‌شده و هنوز ارائه‌نشده‌ی دوره‌های فعال (تومان) */
    public function liability(): float
    {
        return (float) CustomerCourse::query()->where('status', 'active')
            ->get(['amount_paid', 'sessions_used', 'per_session_value', 'refunded_amount'])
            ->sum(fn (CustomerCourse $c) => max(0, $c->amount_paid - $c->refunded_amount - $c->sessions_used * $c->per_session_value));
    }
}
