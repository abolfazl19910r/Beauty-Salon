<?php

namespace App\Listeners;

use App\Events\BookingCreated;
use App\Models\User;
use App\Notifications\AdminNewBookingNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendAdminBookingNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(BookingCreated $event): void
    {
        $admins = User::where('is_admin', true)->get();

        Notification::send($admins, new AdminNewBookingNotification($event->booking));
    }
}
