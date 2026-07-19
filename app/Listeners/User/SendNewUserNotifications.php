<?php

namespace App\Listeners\User;

use App\Events\User\NewUserRegistered;
use App\Models\User;
use App\Notifications\User\NewUserRegisteredNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendNewUserNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(NewUserRegistered $event): void
    {
        $admins = $this->getAdmins();

        Notification::send($admins, new NewUserRegisteredNotification($event->user));
    }

    private function getAdmins()
    {
        return User::where('is_admin', true)
            ->orWhereHas('roles.permissions', function ($query) {
                $query->where('name', 'access_admin_panel');
            })
            ->get();
    }
}
