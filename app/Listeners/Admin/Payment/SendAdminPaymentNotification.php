<?php


namespace App\Listeners\Admin\Payment;

use App\Events\Payment\PaymentSucceeded;
use App\Models\User;
use App\Notifications\Admin\Payment\AdminPaymentReceivedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendAdminPaymentNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(PaymentSucceeded $event): void
    {
        $admins = $this->getAdmins();

        Notification::send($admins, new AdminPaymentReceivedNotification($event->booking));
    }

    /**
     * The same query created in the rest of the admin listeners (is_admin + access via role).
     */
    private function getAdmins()
    {
        return User::where('is_admin', true)
            ->orWhereHas('roles.permissions', function ($query) {
                $query->where('name', 'access_admin_panel');
            })
            ->get();
    }
}
