<?php

namespace App\Console\Commands;

use App\Services\Booking\AttendanceCheckService;
use Illuminate\Console\Command;

/** بسته‌ی 5ه: تأیید حضور با لینک — درخواست ۲۴ ساعت پیش از نوبت، لغو خودکار نوبت تأییدنشده ۳ ساعت مانده */
class RunAttendanceChecks extends Command
{
    protected $signature = 'bookings:attendance-checks';

    protected $description = 'لینک تأیید حضور ۲۴ ساعت پیش از نوبت و لغو نوبت‌های تأییدنشده ۳ ساعت مانده';

    public function handle(AttendanceCheckService $attendance): int
    {
        $result = $attendance->runAll();
        $this->info("Attendance requests sent: {$result['requested']}, cancelled: {$result['cancelled']}");

        return self::SUCCESS;
    }
}
