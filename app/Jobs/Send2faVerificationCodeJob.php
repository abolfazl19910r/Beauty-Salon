<?php

namespace App\Jobs;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\SMSService;
use App\Support\Queues;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class Send2faVerificationCodeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    // Job's own time limit -- even if Kavenegar responds slowly, the Worker won't wait forever for this Job
    public int $timeout = 15;

    public function __construct(
        protected int $userId,
        protected string $code
    ) {
        $this->onQueue(Queues::OTP);
    }

    public function handle(SMSService $smsService, UserRepositoryInterface $userRepository): void
    {
        $user = $userRepository->find($this->userId);

        if (! $user) {
            Log::warning('Send2faVerificationCodeJob: user not found, skipping SMS', [
                'user_id' => $this->userId,
            ]);

            return;
        }

        // ⭐ ۲۰۲۶-۱۱-۰۴: قالب خود 2FA (قبلاً login_verify — کاربر «کد تایید ورود» می‌گرفت و قالب two-factor-auth کاوه‌نگار هرگز استفاده نمی‌شد)
        $template = config('services.kavenegar.templates.two_factor_auth');

        $result = $smsService->sendAuthTemplate($user->phone, $template, [$this->code], $user->salon_id);

        if (! $result) {
            Log::error('Send2faVerificationCodeJob: failed to send 2FA verification code', [
                'user_id' => $user->id,
                'phone' => $user->phone,
            ]);
        }
    }
}
