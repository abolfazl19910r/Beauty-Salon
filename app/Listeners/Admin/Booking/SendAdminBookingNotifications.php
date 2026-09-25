<?php

namespace App\Listeners\Admin\Booking;

use App\Events\Booking\BookingCreated;
use App\Notifications\Booking\AdminNewBookingNotification;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendAdminBookingNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(private readonly UserRepositoryInterface $userRepository) {}

    public function handle(BookingCreated $event): void
    {
        $admins = $this->userRepository->getAdminRecipients($event->booking->salon_id);

        Notification::send($admins, new AdminNewBookingNotification($event->booking));
    }
}
