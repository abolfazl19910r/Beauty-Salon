<?php

namespace App\Listeners\Admin\Withdrawal;

use App\Events\Withdrawal\Requested\WithdrawalRequested;
use App\Notifications\Admin\Withdrawal\Request\AdminNewWithdrawalRequestNotification;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendAdminWithdrawalNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(private readonly UserRepositoryInterface $userRepository) {}

    public function handle(WithdrawalRequested $event): void
    {
        $admins = $this->getAdmins();

        Notification::send($admins, new AdminNewWithdrawalRequestNotification($event->withdrawalRequest));
    }

    private function getAdmins()
    {
        return $this->userRepository->getAdminRecipients();
    }
}
