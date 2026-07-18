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
     * ⭐ Fix: Added 'message' key. It wasn't there before, so the expert dashboard
     * (which uses data['message'] to display the notification text)
     * always just showed the generic text "New Announcement", without saying
     * whether the leave was approved or rejected.
     * @param mixed $notifiable
     * @return array
     */
    public function toDatabase(mixed $notifiable): array
    {
        $statusText = match ($this->leave->status) {
            'approved' => 'تایید شد',
            'rejected' => 'رد شد',
            default    => $this->leave->status,
        };

        $message = sprintf(
            'درخواست مرخصی شما از %s تا %s %s.',
            verta($this->leave->start_date)->format('Y/m/d'),
            verta($this->leave->end_date)->format('Y/m/d'),
            $statusText
        );

        if ($this->leave->status === 'rejected' && $this->leave->reject_reason) {
            $message .= ' دلیل: ' . $this->leave->reject_reason;
        }

        return [
            'message'       => $message,
            'leave_id'      => $this->leave->id,
            'status'        => $this->leave->status,
            'start_date'    => $this->leave->start_date,
            'end_date'      => $this->leave->end_date,
            'reject_reason' => $this->leave->reject_reason,
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
