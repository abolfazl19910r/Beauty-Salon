<?php

namespace App\Notifications\Admin\Report\Export;

use App\Models\ReportExport;
use App\Support\Notifications\NotificationEvents;
use App\Traits\RespectsNotificationSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ReportExportReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use RespectsNotificationSettings;

    public function __construct(
        private readonly ReportExport $reportExport,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->gatedChannels(NotificationEvents::REPORT_EXPORT_READY_ADMIN, ['database']);
    }

    public function toArray(object $notifiable): array
    {
        $isReady = $this->reportExport->status === 'ready';

        return [
            'type' => 'report_export_'.$this->reportExport->status,
            'report_export_id' => $this->reportExport->id,
            'message' => $isReady
                ? "گزارش {$this->reportExport->report_type_text} ({$this->reportExport->format}) شما آماده‌ی دانلود است."
                : "تولید گزارش {$this->reportExport->report_type_text} شما با خطا مواجه شد.",
            'link' => route('admin.reports.exports.index', [], false),
        ];
    }
}
