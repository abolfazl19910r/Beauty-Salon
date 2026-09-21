<?php

namespace App\Listeners\User;

use App\Events\User\NewUserRegistered;
use App\Models\User;
use App\Notifications\User\NewUserRegisteredNotification;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendNewUserNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(private readonly UserRepositoryInterface $userRepository) {}

    public function handle(NewUserRegistered $event): void
    {
        $admins = $this->getAdmins($event->user);

        Notification::send($admins, new NewUserRegisteredNotification($event->user));
    }

    /**
     * ⭐ Customer identity redesign (confirmed 2026-08-30): previously notified EVERY admin in
     * the system whenever anyone, anywhere registered — in a multi-salon world that would leak
     * "a new customer signed up" across salon boundaries to admins who have nothing to do with
     * that salon. Only applies when the new user actually has a salon_id (a customer registered
     * through /s/{slug}/register); admin/specialist creation doesn't fire this event at all, so
     * there's no case here to preserve the old "notify everyone" behavior for.
     */
    private function getAdmins(User $newUser)
    {
        return $this->userRepository->getAdminRecipients($newUser->salon_id);
    }
}
