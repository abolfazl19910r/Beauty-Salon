<?php

namespace App\Console\Commands;

use App\Services\Courses\CourseService;
use Illuminate\Console\Command;

/**
 * دوره‌های فعالی که مهلتشان گذشته ← «منقضی» (جلسه‌های مانده می‌سوزند، بدون بازگشت پول — تصمیم‌های بسته‌ی ب) و خریدهای
 * پرداخت‌نشده‌ی رهاشده ← لغو، تا کد تخفیفشان آزاد شود.
 */
class ExpireCourses extends Command
{
    protected $signature = 'courses:expire';

    protected $description = 'Expire treatment courses past their validity and drop abandoned unpaid purchases';

    public function handle(CourseService $courses): int
    {
        $expired = $courses->expireDue();
        $abandoned = $courses->abandonUnpaid(30);

        $this->info(sprintf('%d دوره منقضی و %d خرید پرداخت‌نشده رها شد.', $expired, $abandoned));

        return self::SUCCESS;
    }
}
