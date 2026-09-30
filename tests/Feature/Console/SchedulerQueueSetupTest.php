<?php

namespace Tests\Feature\Console;

use Tests\TestCase;

/**
 * ⭐ راه‌اندازی سرور: همه‌ی کارهای زمان‌بندی‌شده با یک خط کرون «schedule:run»، و روی هاست بدون supervisor
 * (DirectAdmin) صف هم از همون کرون (QUEUE_WORK_VIA_SCHEDULER). docs/deployment/SCHEDULER_AND_QUEUE.md
 */
class SchedulerQueueSetupTest extends TestCase
{
    public function test_every_scheduled_task_is_registered(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('cancel-unpaid-bookings')
            ->expectsOutputToContain('payments:reconcile')
            ->expectsOutputToContain('bookings:send-reminders')
            ->expectsOutputToContain('wallet:settle-pending')
            ->expectsOutputToContain('payouts:refresh-vandar-tokens')
            ->assertSuccessful();
    }

    public function test_the_queue_is_not_run_from_the_scheduler_by_default(): void
    {
        $this->artisan('schedule:list')->doesntExpectOutputToContain('queue:work')->assertSuccessful();
    }

    public function test_hosts_without_supervisor_can_drain_the_queue_from_the_same_cron_line(): void
    {
        config(['queue.work_via_scheduler' => true]);
        $this->refreshApplicationWithSchedule();

        $this->artisan('schedule:list')
            ->expectsOutputToContain('queue:work --queue='.\App\Support\Queues::workerOrder().' --stop-when-empty --max-time=50')
            ->assertSuccessful();
    }

    /** schedule در boot ساخته می‌شه؛ با config تازه دوباره ساخته بشه. */
    private function refreshApplicationWithSchedule(): void
    {
        $this->refreshApplication();
        config(['queue.work_via_scheduler' => true]);
        $this->app->forgetInstance(\Illuminate\Console\Scheduling\Schedule::class);
    }

    /**
     * تصمیم ۲۰۲۶-۰۹-۳۰: worker اصلی همه‌ی صف‌ها را با ترتیب Queues::workerOrder() می‌خواند و یک worker اختصاصی فقط otp را.
     * worker بدون --queue فقط صف default را می‌خواند و پیامک، پول و گزارش هرگز اجرا نمی‌شدند.
     */
    public function test_docker_and_supervisor_workers_read_every_queue_and_codes_have_their_own_worker(): void
    {
        $order = '--queue='.\App\Support\Queues::workerOrder().' ';

        foreach (['docker-compose.yml', 'deploy/supervisor/mahru-worker.conf'] as $file) {
            $content = file_get_contents(base_path($file));
            $workers = preg_match_all('/queue:work(?:[^\n])*/', $content, $m) ? $m[0] : [];

            $this->assertCount(2, $workers, "{$file}: main worker + otp worker");
            $this->assertStringContainsString($order, $workers[0], "{$file}: main worker queue order");
            $this->assertStringContainsString('--queue=otp ', $workers[1], "{$file}: dedicated otp worker");
        }
    }
}
