<?php

namespace App\Notifications;

use App\Models\Leave;
use App\Services\SMSService;
use Illuminate\Notifications\Notification;

class LeaveStatusNotification extends Notification
{
    private Leave $leave;
    private SMSService $smsService;

    /**
     *
     * @param Leave $leave
     * @return void
     */
    public function __construct(Leave $leave)
    {
        $this->leave = $leave;
        $this->smsService = new SMSService();
    }

    /**
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via(mixed $notifiable): array
    {
        return ['database', 'sms'];
    }

    /**
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toDatabase(mixed $notifiable): array
    {
        return [
            'leave_id' => $this->leave->id,
            'status' => $this->leave->status,
            'start_date' => $this->leave->start_date,
            'end_date' => $this->leave->end_date,
            'reject_reason' => $this->leave->reject_reason
        ];
    }

    /**
     *
     * @param mixed $notifiable
     * @return bool
     */
    public function toSms(mixed $notifiable): bool
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

        return $this->smsService->send($notifiable->phone, $message);
    }
}
