<?php

namespace App\Notifications;

use App\Models\Leave;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeaveStatusNotification extends Notification
{
    use Queueable;

    protected $leave;

    public function __construct(Leave $leave)
    {
        $this->leave = $leave;
    }

    public function via($notifiable): array
    {
        return ['database', 'sms'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'leave_id' => $this->leave->id,
            'status' => $this->leave->status,
            'start_date' => $this->leave->start_date,
            'end_date' => $this->leave->end_date,
            'reject_reason' => $this->leave->reject_reason
        ];
    }

    public function toSms($notifiable)
    {
        $statusText = match($this->leave->status) {
            'approved' => 'تایید',
            'rejected' => 'رد',
            default => $this->leave->status
        };

        $message = sprintf(
            'همکار گرامی، درخواست مرخصی شما از تاریخ %s تا %s %s شد.',
            verta($this->leave->start_date)->format('Y/m/d'),
            verta($this->leave->end_date)->format('Y/m/d'),
            $statusText
        );

        if ($this->leave->status === 'rejected' && $this->leave->reject_reason) {
            $message .= "\nدلیل: " . $this->leave->reject_reason;
        }

        // ارسال از طریق سرویس پیامک
        return [
            'phone' => $notifiable->phone,
            'message' => $message
        ];
    }
}
