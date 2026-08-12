<?php

namespace Tests\Feature\Admin;

use App\Jobs\GeneratePdfReportJob;
use App\Models\Booking;
use App\Models\ReportExport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminReportExportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_export_creates_a_pending_record_and_dispatches_the_job(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->admin)->post('/admin/reports/export', [
            'format' => 'excel',
            'report_type' => 'daily',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect(route('admin.reports.exports.index'));
        $this->assertDatabaseHas('report_exports', [
            'admin_user_id' => $this->admin->id,
            'format' => 'excel',
            'report_type' => 'daily',
            'status' => 'pending',
        ]);
        Queue::assertPushed(GeneratePdfReportJob::class);
    }

    public function test_export_defaults_format_to_excel_and_report_type_to_daily_when_omitted(): void
    {
        Queue::fake();

        $this->actingAs($this->admin)->post('/admin/reports/export', []);

        $this->assertDatabaseHas('report_exports', ['format' => 'excel', 'report_type' => 'daily']);
    }

    public function test_export_index_lists_all_admins_requests_not_just_the_current_admin(): void
    {
        $otherAdmin = User::factory()->create(['is_admin' => true]);
        ReportExport::factory()->for($this->admin, 'adminUser')->create();
        ReportExport::factory()->for($otherAdmin, 'adminUser')->create();

        $response = $this->actingAs($this->admin)->get('/admin/reports/exports');

        $response->assertOk();
        $this->assertCount(2, $response->viewData('exports'));
    }

    public function test_download_of_a_not_ready_export_redirects_with_an_error(): void
    {
        $export = ReportExport::factory()->for($this->admin, 'adminUser')->create(['status' => 'pending']);

        $response = $this->actingAs($this->admin)->get("/admin/reports/exports/{$export->id}/download");

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_download_of_a_ready_export_streams_the_file(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('report-exports/1.xlsx', 'fake-excel-content');
        $export = ReportExport::factory()->for($this->admin, 'adminUser')->create([
            'status' => 'ready',
            'format' => 'excel',
            'file_path' => 'report-exports/1.xlsx',
        ]);

        $response = $this->actingAs($this->admin)->get("/admin/reports/exports/{$export->id}/download");

        $response->assertOk();
    }

    public function test_download_of_ready_status_but_missing_file_on_disk_is_not_downloadable(): void
    {
        Storage::fake('local');
        $export = ReportExport::factory()->for($this->admin, 'adminUser')->create([
            'status' => 'ready',
            'file_path' => 'report-exports/missing.xlsx',
        ]);

        $response = $this->actingAs($this->admin)->get("/admin/reports/exports/{$export->id}/download");

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_job_generates_a_real_excel_file_and_marks_the_export_ready(): void
    {
        Storage::fake('local');
        Booking::factory()->count(2)->create(['payment_status' => 'paid', 'created_at' => now()]);
        $export = ReportExport::factory()->for($this->admin, 'adminUser')->create([
            'format' => 'excel',
            'report_type' => 'daily',
            'filters' => ['start_date' => now()->format('Y-m-d'), 'end_date' => now()->format('Y-m-d')],
            'status' => 'pending',
        ]);

        (new GeneratePdfReportJob($export->id))->handle(app(\App\Services\Admin\Report\AdminReportService::class));

        $export->refresh();
        $this->assertSame('ready', $export->status);
        $this->assertNotNull($export->file_path);
        Storage::disk('local')->assertExists($export->file_path);
        $this->assertGreaterThan(0, Storage::disk('local')->size($export->file_path));
        $this->assertDatabaseHas('user_notifications', ['notifiable_id' => $this->admin->id]);
    }

    public function test_job_skips_processing_if_export_is_no_longer_pending(): void
    {
        $export = ReportExport::factory()->for($this->admin, 'adminUser')->create(['status' => 'ready', 'file_path' => 'already-done.xlsx']);

        (new GeneratePdfReportJob($export->id))->handle(app(\App\Services\Admin\Report\AdminReportService::class));

        // untouched — the job must not overwrite an already-finished export
        $export->refresh();
        $this->assertSame('already-done.xlsx', $export->file_path);
    }

    public function test_job_marks_export_as_failed_and_notifies_when_generation_throws(): void
    {
        // The pdf-report font files are not present in this environment (mPDF needs a real
        // storage/fonts/*.ttf that is deployed separately, not committed to the repo) — this
        // reliably exercises the job's catch branch with a genuine exception, not a mock.
        $export = ReportExport::factory()->for($this->admin, 'adminUser')->create([
            'format' => 'pdf',
            'report_type' => 'daily',
            'filters' => ['start_date' => now()->format('Y-m-d'), 'end_date' => now()->format('Y-m-d')],
            'status' => 'pending',
        ]);

        (new GeneratePdfReportJob($export->id))->handle(app(\App\Services\Admin\Report\AdminReportService::class));

        $export->refresh();
        $this->assertSame('failed', $export->status);
        $this->assertNotNull($export->error_message);
        $this->assertDatabaseHas('user_notifications', ['notifiable_id' => $this->admin->id]);
    }

    public function test_job_handles_a_missing_report_export_record_gracefully(): void
    {
        // Should not throw even though 999999 doesn't exist.
        (new GeneratePdfReportJob(999999))->handle(app(\App\Services\Admin\Report\AdminReportService::class));
        $this->assertTrue(true);
    }

    public function test_failed_hook_marks_pending_or_processing_export_as_failed(): void
    {
        $export = ReportExport::factory()->for($this->admin, 'adminUser')->create(['status' => 'processing']);

        (new GeneratePdfReportJob($export->id))->failed(new \Exception('queue timeout'));

        $export->refresh();
        $this->assertSame('failed', $export->status);
        $this->assertSame('queue timeout', $export->error_message);
    }

    public function test_non_admin_cannot_request_an_export(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->post('/admin/reports/export', [])->assertStatus(403);
    }
}
