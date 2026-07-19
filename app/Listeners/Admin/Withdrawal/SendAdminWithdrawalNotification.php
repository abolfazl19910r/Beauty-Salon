<?php

namespace App\Listeners\Admin\Withdrawal;

use App\Events\Withdrawal\Requested\WithdrawalRequested;
use App\Models\User;
use App\Notifications\Admin\Withdrawal\Request\AdminNewWithdrawalRequestNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendAdminWithdrawalNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(WithdrawalRequested $event): void
    {
        $admins = $this->getAdmins();

        Notification::send($admins, new AdminNewWithdrawalRequestNotification($event->withdrawalRequest));
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
