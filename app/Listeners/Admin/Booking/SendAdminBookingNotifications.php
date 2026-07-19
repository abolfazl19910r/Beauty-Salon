<?php

namespace App\Listeners\Admin\Booking;

use App\Events\Booking\BookingCreated;
use App\Models\User;
use App\Notifications\Booking\AdminNewBookingNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendAdminBookingNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(BookingCreated $event): void
    {
        $admins = $this->getAdmins();

        Notification::send($admins, new AdminNewBookingNotification($event->booking));
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
