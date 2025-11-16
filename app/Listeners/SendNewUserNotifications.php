<?php

namespace App\Listeners;

use App\Events\NewUserRegistered;
use App\Models\User;
use App\Notifications\NewUserRegisteredNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendNewUserNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(NewUserRegistered $event): void
    {
        $admins = User::where('is_admin', true)->get();

        Notification::send($admins, new NewUserRegisteredNotification($event->user));
    }
}
