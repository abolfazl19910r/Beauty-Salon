<?php

namespace App\Services\Specialist;

use App\Models\Specialist;
use App\Repositories\Contracts\SpecialistRepositoryInterface;
use App\Repositories\Contracts\SpecialistScheduleRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * ذخیره‌ی برنامه‌ی هفتگی متخصص و «تأیید خودکار نوبت‌ها» — مشترک پنل وب و API اپ همکار (بسته‌ی ۲).
 */
class SpecialistScheduleService
{
    public function __construct(
        protected readonly SpecialistRepositoryInterface $specialistRepository,
        protected readonly SpecialistScheduleRepositoryInterface $scheduleRepository,
    ) {}

    /**
     * @param  array<int, array{day_of_week: int, is_active?: mixed, start_time?: ?string, end_time?: ?string, break_start?: ?string, break_end?: ?string}>  $schedules
     * @param  bool|null  $autoConfirm  null = بدون تغییر
     */
    public function replace(Specialist $specialist, array $schedules, ?bool $autoConfirm): void
    {
        DB::transaction(function () use ($specialist, $schedules, $autoConfirm) {
            if ($autoConfirm !== null) {
                $this->specialistRepository->update($specialist, ['auto_confirm_bookings' => $autoConfirm]);
            }

            $this->scheduleRepository->replaceForSpecialist($specialist, $schedules);
        });
    }
}
