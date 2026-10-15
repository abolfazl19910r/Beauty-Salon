<?php

namespace Tests\Feature\SuperAdmin\Fixtures;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/** کار آزمایشی که همیشه شکست می‌خورد (هشدار «کار ناموفق صف» ابرمدیر) */
class AlwaysFailingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle(): void
    {
        throw new \RuntimeException('عمداً شکست خورد');
    }
}
