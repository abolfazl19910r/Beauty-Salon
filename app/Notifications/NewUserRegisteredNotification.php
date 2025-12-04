<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class NewUserRegisteredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private User $newUser;

    public function __construct(User $newUser)
    {
        $this->newUser = $newUser;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $link = '#';

        try {
            $link = route('admin.users.show', $this->newUser->id, absolute: true);
        } catch (\InvalidArgumentException $e) {
            Log::error("Route 'admin.users.show' not defined in queued notification.", ['error' => $e->getMessage()]);
        }

        return [
            'type' => 'new_user_registered',
            'user_id' => $this->newUser->id,
            'name' => $this->newUser->name,
            'message' => 'یک کاربر جدید ثبت‌نام کرد: ' . $this->newUser->name,
            'link' => $link,
        ];
    }
}
