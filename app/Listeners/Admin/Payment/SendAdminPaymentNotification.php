<?php

namespace App\Listeners\Admin\Payment;

use App\Events\Payment\PaymentSucceeded;
use App\Notifications\Admin\Payment\AdminPaymentReceivedNotification;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendAdminPaymentNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(private readonly UserRepositoryInterface $userRepository) {}

    public function handle(PaymentSucceeded $event): void
    {
        $admins = $this->getAdmins();

        Notification::send($admins, new AdminPaymentReceivedNotification($event->booking));
    }

    private function getAdmins()
    {
        return $this->userRepository->getAdminRecipients();
    }
}
