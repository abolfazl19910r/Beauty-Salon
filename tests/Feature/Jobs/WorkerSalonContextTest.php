<?php

namespace Tests\Feature\Jobs;

use App\Jobs\CancelUnpaidBookings;
use App\Jobs\GeneratePdfReportJob;
use App\Models\Booking;
use App\Models\ReportExport;
use App\Models\Salon;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * queue:work یک پردازه‌ی ماندگار است و چند job را پشت‌سرهم اجرا می‌کند. سالنی که یک job برای کار خودش ست می‌کند
 * نباید به job بعدی برسد؛ وگرنه global scope سالن job بعدی را به داده‌های همان سالن محدود می‌کند.
 */
class WorkerSalonContextTest extends TestCase
{
    use RefreshDatabase;

    private function exportOf(Salon $salon): ReportExport
    {
        $previous = app(CurrentSalon::class)->get();
        app(CurrentSalon::class)->set($salon);
        $export = ReportExport::factory()->create(['status' => 'ready']);
        app(CurrentSalon::class)->set($previous);

        return $export;
    }

    public function test_a_report_job_for_one_salon_does_not_narrow_the_next_job_in_the_same_worker(): void
    {
        $booking = Booking::factory()->create(['status' => 'pending_payment', 'payment_status' => 'unpaid']);
        Booking::withoutGlobalScopes()->whereKey($booking->id)->update(['created_at' => now()->subHour()]);
        $export = $this->exportOf(Salon::factory()->create());

        app(CurrentSalon::class)->clear();
        config(['queue.default' => 'database']);
        dispatch(new GeneratePdfReportJob($export->id));
        dispatch(new CancelUnpaidBookings);

        $seen = [];
        $order = [];
        Queue::before(function ($event) use (&$seen, &$order) {
            $seen[$event->job->resolveName()] = app(CurrentSalon::class)->id();
            $order[] = $event->job->resolveName();
        });

        // --memory: پردازه‌ی کل سوییت از سقف پیش‌فرض ۱۲۸MB worker بیشتر حافظه دارد و worker بعد از job اول می‌ایستاد.
        // هر دو job روی صف خودشان‌اند؛ worker عمداً اول reports را می‌خواند تا job گزارش واقعاً قبل از job بعدی اجرا شود
        Artisan::call('queue:work', ['--stop-when-empty' => true, '--memory' => 4096, '--queue' => \App\Support\Queues::REPORTS.','.\App\Support\Queues::PAYMENTS]);

        $this->assertSame([GeneratePdfReportJob::class, CancelUnpaidBookings::class], $order);
        $this->assertArrayHasKey(CancelUnpaidBookings::class, $seen);
        $this->assertNull($seen[CancelUnpaidBookings::class]);
        $this->assertSame('cancelled', Booking::withoutGlobalScopes()->find($booking->id)->status);
    }

    public function test_a_report_job_run_inline_leaves_the_callers_salon_context_as_it_was(): void
    {
        $export = $this->exportOf(Salon::factory()->create());
        app(CurrentSalon::class)->clear();

        (new GeneratePdfReportJob($export->id))->handle(
            app(\App\Services\Admin\Report\AdminReportService::class),
            app(\App\Repositories\Contracts\ReportExportRepositoryInterface::class),
        );

        $this->assertNull(app(CurrentSalon::class)->id());
    }
}
