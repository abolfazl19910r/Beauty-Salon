<?php

namespace App\Console\Commands;

use App\Services\Courses\CourseReminderService;
use Illuminate\Console\Command;

/** یادآوری روزانه‌ی جلسه‌ی بعد و پایان مهلت دوره‌های درمانی (بسته‌ی ب، ۲۰۲۶-۱۰-۱۴) */
class SendCourseReminders extends Command
{
    protected $signature = 'courses:remind';

    protected $description = 'Text customers whose next treatment-course session is due or whose course is about to expire';

    public function handle(CourseReminderService $reminders): int
    {
        $sent = $reminders->runAll();

        $this->info(sprintf('%d یادآوری دوره برای %d سالن فرستاده شد.', array_sum($sent), count($sent)));

        return self::SUCCESS;
    }
}
